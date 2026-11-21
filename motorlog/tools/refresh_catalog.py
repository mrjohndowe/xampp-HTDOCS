#!/usr/bin/env python3
"""Refresh the optional local NHTSA vPIC catalog; Python 3.10+, no packages."""
import argparse
import csv
import datetime as dt
import io
import json
import os
from pathlib import Path
import time
from urllib.parse import quote
from urllib.request import Request, urlopen

API = 'https://vpic.nhtsa.dot.gov/api/vehicles/'
TYPES = {'car': ('Passenger Car', 2), 'motorcycle': ('Motorcycle', 1)}


def fetch(route):
    error = None
    for delay in (0, 3, 12):
        if delay:
            time.sleep(delay)
        try:
            req = Request(API + route, headers={'User-Agent': 'Motorlog-Catalog/1.0', 'Accept': 'text/csv, application/json'})
            with urlopen(req, timeout=90) as response:
                return response.read().decode('utf-8-sig')
        except (OSError, ValueError) as exc:
            error = exc
    raise RuntimeError(f'{route}: {error}')


def build(read):
    makes, counts = {}, {}
    for key, (label, type_id) in TYPES.items():
        query = quote(label.lower())
        make_result = json.loads(read(f'GetMakesForVehicleType/{query}?format=json'))
        ids = set()
        for row in make_result['Results']:
            if int(row['VehicleTypeId']) != type_id or row['VehicleTypeName'] != label:
                raise ValueError(f'Unexpected make type in {label} response')
            mid = int(row['MakeId'])
            ids.add(mid)
            makes.setdefault(mid, {'id': mid, 'name': row['MakeName'].strip(), 'types': {}})['types'][key] = {}
        if len(ids) < 100 or len(ids) != int(make_result['Count']):
            raise ValueError(f'Incomplete {label} make response')
        typed = list(csv.DictReader(io.StringIO(read(f'GetModelsForMakeYear/make/%25/vehicletype/{query}?format=csv'))))
        for row in typed:
            mid = int(row['make_id'])
            if row['vehicletypename'] != label or int(row['vehicletypeid']) != type_id:
                raise ValueError(f'Unexpected model type in {label} response')
            if mid not in ids:
                raise ValueError(f'Model make {mid} absent from the {label} make result; snapshot remains unchanged')
            if row['model_name'].strip():
                makes[mid]['types'][key][int(row['model_id'])] = row['model_name'].strip()
        count = sum(len(makes[mid]['types'][key]) for mid in ids)
        if count < (1000 if key == 'car' else 5000):
            raise ValueError(f'Unexpectedly short {label} model response')
        counts[key] = {'makes': len(ids), 'models': count}
    unique = {(mid, model) for mid, make in makes.items() for group in make['types'].values() for model in group}
    for make in makes.values():
        make['types'] = {k: sorted(entries.items(), key=lambda r: (r[1].casefold(), r[0])) for k, entries in make['types'].items()}
    return {'source': 'NHTSA Vehicle Product Information Catalog (vPIC)', 'source_url': 'https://vpic.nhtsa.dot.gov/api/',
            'retrieved': dt.datetime.now(dt.timezone.utc).date().isoformat(),
            'types': {k: label for k, (label, _) in TYPES.items()},
            'counts': {'makes': len(makes), 'models': len(unique), 'typed_models': sum(c['models'] for c in counts.values()),
                       'both_types': sum(len(m['types']) == 2 for m in makes.values()), **counts},
            'makes': sorted(makes.values(), key=lambda m: (m['name'].casefold(), m['id']))}


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--output', type=Path, default=Path(__file__).resolve().parents[1] / 'catalog' / 'vpic.json')
    p.add_argument('--input-dir', type=Path, help='Import four previously downloaded official source files (see README)')
    p.add_argument('--allow-smaller', action='store_true', help='Allow a verified upstream contraction of over 5 percent')
    args = p.parse_args()
    try:
        def read(route):
            if not args.input_dir:
                return fetch(route)
            ext = 'csv' if route.endswith('csv') else 'json'
            name = 'motorcycle' if '/motorcycle?' in route else 'car'
            return (args.input_dir / f'{name}.{ext}').read_text(encoding='utf-8-sig')
        output = build(read)
        if args.output.exists() and not args.allow_smaller:
            old = json.loads(args.output.read_text(encoding='utf-8'))['counts']
            if any(output['counts'][key][stat] < .95 * old[key][stat] for key in TYPES for stat in ('makes', 'models')):
                raise ValueError('A type lost more than 5% of records; verify the source before using --allow-smaller')
        args.output.parent.mkdir(parents=True, exist_ok=True)
        stage = args.output.with_name(args.output.name + '.new')
        stage.write_text(json.dumps(output, ensure_ascii=False, separators=(',', ':')) + '\n', encoding='utf-8')
        os.replace(stage, args.output)
        print(json.dumps(output['counts'], indent=2))
    except Exception as exc:
        p.exit(1, f'Catalog unchanged: {exc}\n')


if __name__ == '__main__':
    main()
