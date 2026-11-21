import json,pathlib
# A shared declarative UI contract, consumed by both GUI clients. Validation remains in PHP.
F={}
def form(k,title,fields):
 F[k]={'title':title,'fields':[dict(zip(('key','label','type'),x.split('|'))) for x in fields.split('\n') if x]}
form('vehicles','Vehicles','''make|Make · catalog or manual|make
model|Model · catalog or manual|model
year|Year|number
vin|VIN|text
plate|Plate|text
color|Color|text
baseline_date|Vehicle / engine baseline date|date
odometer|Vehicle baseline odometer|number
engine_miles|Engine mileage at baseline|number
notes|Notes|textarea
cover_id|Cover photo ID (from gallery)|number''')
form('trips','Trips','''vehicle_id|Vehicle|ref:vehicles
driver_id|Driver (optional)|ref:drivers
date|Date|date
start|Starting odometer|number
end|Ending odometer|number
purpose|Purpose|text
notes|Notes|textarea''')
form('expenses','Expenses & fuel','''vehicle_id|Vehicle|ref:vehicles
date|Date|date
category|Category|select:Fuel,Repairs,Maintenance,Parts,Other
vendor|Vendor|text
description|Description|textarea
amount|Total amount (fuel calculated on server)|number
odometer|Fuel odometer|number
gallons|Gallons (US)|number
unit_price|Price per US gallon|number
full|Full tank reached|checkbox
notes|Notes|textarea''')
form('mechanics','Mechanics','''name|Name|text
phone|Phone|text
email|Email|text
address|Address|textarea
notes|Notes|textarea''')
form('invoices','Repair invoices','''vehicle_id|Vehicle|ref:vehicles
mechanic_id|Mechanic|ref:mechanics
number|Invoice number|text
date|Invoice date|date
due_date|Due date|date
amount|Repair amount (counted once in expenses)|number
description|Repair description|textarea
notes|Notes|textarea''')
form('payments','Invoice payments','''invoice_id|Invoice|ref:invoices
date|Payment date|date
amount|Payment amount|number
method|Payment method|text
notes|Notes|textarea''')
form('drivers','Drivers','''name|Full name|text
phone|Phone|text
email|Email|text
license|License number|text
jurisdiction|Licensing state / jurisdiction|text
expires|License expiration|date
emergency|Emergency contact|textarea
active|Active driver|checkbox
notes|Notes|textarea''')
form('driver_records','Driver records','''driver_id|Driver|ref:drivers
date|Record date|date
type|Record type|select:Training,Qualification,License renewal,Incident,Violation,General note
notes|Record details|textarea''')
form('engines','Engine history','''vehicle_id|Vehicle|ref:vehicles
date|Installation / replacement date|date
odometer|Vehicle odometer at installation|number
engine_miles|Engine mileage at installation|number
serial|Serial number|text
reason|Replacement reason|text
notes|Notes|textarea''')
form('inspections','Weekly inspections','''vehicle_id|Vehicle|ref:vehicles
driver_id|Assigned driver (optional)|ref:drivers
inspector|Inspector identification / name|text
date|Inspection date|date
time|Inspection time|time
odometer|Odometer|number
unit|Distance unit|select:mi,km
year|Year · review prefill|text
make|Make · review prefill|text
model|Model · review prefill|text
vin|VIN · review prefill|text
plate|Plate · review prefill|text
comments|Overall comments|textarea
acknowledged|I personally reviewed and completed this inspection|checkbox
status|Status|select:Draft,Completed''')
form('issues','Inspection follow-up','''inspection_id|Inspection|ref:inspections
item|Checklist item key|text
status|Follow-up status|select:Open,In progress,Resolved
original_result|Original result (read-only evidence in inspection)|text
original_notes|Original finding|textarea
action|Action taken|textarea
responsible|Responsible person|text
resolved_date|Resolution date|date
notes|Follow-up notes|textarea''')
form('sales','Bill of Sale','''vehicle_id|Vehicle (optional, or manual details)|ref:vehicles
jurisdiction|Template / jurisdiction (general editable form)|text
buyer_name|Buyer legal name|text
buyer_address|Buyer mailing address|textarea
buyer_contact|Buyer contact (optional)|text
seller_name|Seller legal name|text
seller_address|Seller mailing address|textarea
seller_contact|Seller contact (optional)|text
date|Sale date|date
time|Sale time|time
location|Sale location|text
year|Vehicle year|text
make|Make · review prefill|make
model|Model · review prefill|model
vin|VIN|text
plate|Plate|text
color|Color|text
odometer|Odometer disclosure reading|number
unit|Odometer unit|select:mi,km
disclosure|Mileage disclosure (choose deliberately)|select:Actual mileage,Exceeds mechanical limits,Not actual mileage
price|Sale price|number
currency|Currency|text
deposit|Deposit|number
balance|Remaining balance (server calculated)|number
payment_method|Payment method|text
payment_status|Payment status (not automatic)|select:Unpaid,Deposit received,Paid,Other
payment_due|Payment due date (if applicable)|date
payment_terms|Agreed payment terms|textarea
condition|Vehicle condition|textarea
defects|Known defects (state none if reviewed)|textarea
accessories|Included accessories|textarea
title_liens|Title / lien disclosure (explicitly review)|textarea
delivery|Delivery / possession date and arrangements|textarea
warranty|Warranty / as-is terms (write agreed terms)|textarea
additional_terms|Additional terms (state none if reviewed)|textarea
witness|Witness / notary fields (optional, jurisdiction dependent)|textarea
reviewed|I reviewed all disclosures and agreed terms before finalization|checkbox
status|Agreement status|select:Draft,Finalized''')
form('sale_templates','Sale templates',''''name|Template name|text
jurisdiction|Jurisdiction / state|text
instructions|Review instructions (not legal advice)|textarea
condition|Condition wording|textarea
defects|Defect-disclosure prompts|textarea
accessories|Accessories prompts|textarea
title_liens|Title / lien disclosure prompts|textarea
warranty|Warranty wording (must be reviewed)|textarea
additional_terms|Additional terms|textarea
payment_terms|Payment terms|textarea''')
pathlib.Path(__file__).resolve().parents[1].joinpath('data/forms.json').write_text(json.dumps(F,indent=2))
