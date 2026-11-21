"""Business-date reporting and portable exports."""

import csv
import io
import json
from calendar import monthrange
from datetime import date, timedelta
from decimal import Decimal
from xml.sax.saxutils import escape

from flask import send_file

import cash

METRICS = (
    ("opening_cents", "Opening"), ("expected_cents", "Expected"),
    ("actual_cents", "Actual"), ("variance_cents", "Over / short"),
)
MOVEMENT_LABELS = {
    "sale": "Sales", "deposit": "Deposits", "payout": "Payouts",
    "withdrawal": "Withdrawals", "addition": "Additions", "adjustment": "Adjustments",
}


def date_range(args, today):
    period = args.get("period", "week")
    if period not in {"day", "week", "month", "custom"}:
        raise cash.Problem("Select day, week, month or custom.")
    end = date.fromisoformat(cash.parse_date(args.get("end") or today))
    start = end
    if period == "custom":
        start = date.fromisoformat(cash.parse_date(args.get("start") or today))
    elif period == "week":
        start -= timedelta(days=start.weekday())
        end = start + timedelta(days=min(6, (date.max - start).days))
    elif period == "month":
        start = end.replace(day=1)
        end = end.replace(day=monthrange(end.year, end.month)[1])
    if not 0 <= (end - start).days <= 365:
        raise cash.Problem("Choose an ordered range of at most 366 days.")
    return period, start.isoformat(), end.isoformat()


def build(conn, args, today):
    period, start, end = date_range(args, today)
    movement = args.get("movement", "all")
    if movement not in {"all", *MOVEMENT_LABELS}:
        raise cash.Problem("Select a supported movement filter.")
    rows = conn.execute(
        "SELECT id FROM drawers WHERE business_date BETWEEN ? AND ? ORDER BY business_date DESC, id DESC",
        (start, end)).fetchall()
    drawers = [cash.snapshot(conn, row["id"], history=True) for row in rows]
    movements = {kind: sum(drawer["totals"][kind] for drawer in drawers) for kind in MOVEMENT_LABELS}
    daily = {}
    for drawer in drawers:
        day = daily.setdefault(drawer["business_date"], {"sale": 0, "deposit": 0, "payout": 0})
        for key in day:
            day[key] += drawer["totals"][key]
    closed = [drawer for drawer in drawers if drawer["closed_at"]]
    return {"period": period, "start": start, "end": end, "drawer_count": len(drawers),
            "closed_count": len(closed), "movement": movement, "movements": movements,
            "over_cents": sum(max(0, drawer["variance_cents"]) for drawer in closed),
            "short_cents": sum(min(0, drawer["variance_cents"]) for drawer in closed),
            "daily": dict(sorted(daily.items())), "drawers": drawers,
            "transactions": [dict(tx, drawer_name=drawer["name"], business_date=drawer["business_date"])
                             for drawer in drawers for tx in drawer["transactions"]
                             if movement in {"all", tx["kind"]}]}


def tables(report):
    summary = []
    movements = []
    denominations = []
    names = {drawer["id"]: drawer["name"] for drawer in report["drawers"]}
    for drawer in report["drawers"]:
        summary.append([drawer["id"], drawer["name"], drawer["business_date"],
                        "Closed" if drawer["closed_at"] else "Open",
                        *[drawer[key] for key, _ in METRICS],
                        *[drawer["totals"][key] for key in MOVEMENT_LABELS]])
        events = [(item, item["kind"]) for item in drawer["counts"]]
        events += [(item, "deposit") for item in drawer["transactions"] if item["kind"] == "deposit"]
        for event, kind in events:
            for code, label, value, _ in cash.DENOMINATIONS:
                count = event["quantities"][code]
                size = drawer["roll_sizes"].get(code, 0)
                rolls = count.get("rolls", 0)
                subtotal = (count["loose"] + rolls * size) * value
                if subtotal:
                    denominations.append([drawer["id"], kind, event["id"], event["created_at"],
                                          label, count["loose"], rolls, size, subtotal])
    for item in report["transactions"]:
        movements.append([item["id"], names[item["drawer_id"]], item["business_date"],
                          item["kind"], item["amount_cents"], item["note"], item["username"]])
    return [
        ("Drawers", ["ID", "Drawer", "Date", "Status", *[label for _, label in METRICS],
                     *MOVEMENT_LABELS.values()], summary, set(range(4, 14))),
        ("Movements", ["ID", "Drawer", "Date", "Type", "Amount", "Note", "Operator"],
         movements, {4}),
        ("Denominations", ["Drawer ID", "Event", "Event ID", "Recorded UTC", "Denomination",
                           "Loose", "Rolls", "Roll size", "Subtotal"], denominations, {8}),
    ]


def safe_csv(value):
    if isinstance(value, str) and value.lstrip().startswith(("=", "+", "-", "@")):
        return "'" + value
    return value


def display_row(row, currency):
    return [cash.money(value) if index in currency else value for index, value in enumerate(row)]


def download(report, kind):
    parts = tables(report)
    filename = f"cash-{report['start']}-to-{report['end']}.{kind}"
    output = io.BytesIO()
    if kind == "csv":
        stream = io.StringIO(newline="")
        writer = csv.writer(stream)
        for title, headers, rows, currency in parts:
            writer.writerow([title])
            writer.writerow(headers)
            for row in rows:
                writer.writerow([format(Decimal(value).scaleb(-2), ".2f") if index in currency
                                 else safe_csv(value) for index, value in enumerate(row)])
            writer.writerow([])
        output.write(stream.getvalue().encode("utf-8-sig"))
        content_type = "text/csv; charset=utf-8"
    elif kind == "xlsx":
        from openpyxl import Workbook
        from openpyxl.styles import Font, PatternFill
        from openpyxl.utils import get_column_letter

        book = Workbook()
        book.remove(book.active)
        for title, headers, rows, currency in parts:
            sheet = book.create_sheet(title)
            sheet.append(headers)
            for index, label in enumerate(headers, 1):
                cell = sheet.cell(1, index)
                cell.font = Font(bold=True, color="FFFFFF")
                cell.fill = PatternFill("solid", fgColor="164E43")
                sheet.column_dimensions[get_column_letter(index)].width = max(len(label) + 4, 17)
            for row in rows:
                sheet.append([Decimal(value).scaleb(-2) if index in currency else value
                              for index, value in enumerate(row)])
                for index, value in enumerate(row):
                    cell = sheet.cell(sheet.max_row, index + 1)
                    if index in currency:
                        cell.number_format = '"$"#,##0.00;-"$"#,##0.00'
                    elif isinstance(value, str):
                        cell.data_type = "s"
            sheet.freeze_panes = "A2"
            sheet.auto_filter.ref = sheet.dimensions
        book.save(output)
        content_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    else:
        from reportlab.lib import colors
        from reportlab.lib.pagesizes import landscape, letter
        from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
        from reportlab.platypus import LongTable, PageBreak, Paragraph, SimpleDocTemplate, Spacer

        styles = getSampleStyleSheet()
        body = ParagraphStyle("cell", fontSize=7, leading=9, splitLongWords=True)
        head = ParagraphStyle("column", parent=body, textColor=colors.white, fontName="Helvetica-Bold")
        pdf = SimpleDocTemplate(output, pagesize=landscape(letter), leftMargin=30, rightMargin=30,
                                topMargin=32, bottomMargin=32, title="Cash activity report")
        story = [Paragraph("Cash activity report", styles["Title"]),
                 Paragraph(f"{report['start']} to {report['end']}", styles["Normal"]), Spacer(1, 12)]
        for index, (title, headers, rows, currency) in enumerate(parts):
            if index:
                story.append(PageBreak())
            story.append(Paragraph(title, styles["Heading2"]))
            if not rows:
                story.append(Paragraph("No entries for this period.", styles["Normal"]))
                continue
            values = [[Paragraph(escape(label), head) for label in headers]]
            values.extend([Paragraph(escape(str(value)), body) for value in display_row(row, currency)]
                          for row in rows)
            if title == "Movements":
                weights = [0.5, 1.2, 1, 0.9, 1, 3, 1]
            elif title == "Denominations":
                weights = [0.8, 1, 0.8, 1.8, 1, 0.65, 0.65, 0.7, 1]
            else:
                weights = [0.45, 1.45, 1.05, 0.7, *([1] * 10)]
            table = LongTable(values, colWidths=[pdf.width * weight / sum(weights) for weight in weights],
                              repeatRows=1, hAlign="LEFT")
            table.setStyle([("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#164e43")),
                            ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#eef5f2")]),
                            ("VALIGN", (0, 0), (-1, -1), "TOP"),
                            ("BOTTOMPADDING", (0, 0), (-1, -1), 6)])
            story.append(table)
        pdf.build(story)
        content_type = "application/pdf"
    output.seek(0)
    return send_file(output, download_name=filename, as_attachment=True, mimetype=content_type)
