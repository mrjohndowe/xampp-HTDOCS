"""Build the bundled fallback catalog from type-filtered vPIC responses already retrieved,
plus the entire Motorcycle projection of a pinned VehiclesDB commit.
This is NOT a replacement for a complete vPIC snapshot; see CATALOG.md.
No file is replaced until all source files have passed validation.
Usage: python tools/import_catalog.py [--commit SHA]
"""
import argparse, datetime, hashlib, json, pathlib, urllib.request
ROOT=pathlib.Path(__file__).resolve().parents[1]
def fetch(url):
 with urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'VehicleTracker/1.0'}),timeout=60) as r:return r.read()
def main():
 p=argparse.ArgumentParser();p.add_argument('--commit');args=p.parse_args()
 commit=args.commit or json.loads(fetch('https://api.github.com/repos/vehiclesdb/vehiclesdb/commits/main'))['sha']
 base=f'https://raw.githubusercontent.com/vehiclesdb/vehiclesdb/{commit}/'
 makes=json.loads(fetch(base+'catalog/motorcycle/makes.json'));models=json.loads(fetch(base+'catalog/motorcycle/models.json'))
 attribution=fetch(base+'ATTRIBUTION.md');sources=fetch(base+'SOURCES.md');manifest=json.loads(fetch(base+'manifest.json'))
 rows={}
 for m in makes:rows[m['name'].casefold()]={'id':'vdb:'+m['id'],'name':m['name'],'types':['Motorcycle'],'models':[]}
 lookup={m['id']:m['name'].casefold() for m in makes}
 for m in models:
  if m['kind']!='motorcycle':raise ValueError('Unexpected source kind')
  rows[lookup[m['make_id']]]['models'].append({'id':'vdb:'+m['id'],'name':m['name'],'types':['Motorcycle']})
 car_count=0
 for path in (ROOT/'data/catalog-cache').glob('*-car.json'):
  for m in json.loads(path.read_text()):
   if m.get('VehicleTypeId')!=2 or m.get('VehicleTypeName')!='Passenger Car':raise ValueError('Non-passenger vPIC model in cache')
   key=m['Make_Name'].casefold();row=rows.setdefault(key,{'id':m['Make_ID'],'name':m['Make_Name'],'types':[],'models':[]})
   if 'Passenger Car' not in row['types']:row['types'].append('Passenger Car')
   row['models'].append({'id':m['Model_ID'],'name':m['Model_Name'],'types':['Passenger Car']});car_count+=1
 output={'source':'NHTSA vPIC partial passenger-car retrieval + VehiclesDB Motorcycle projection','retrieved':datetime.datetime.now(datetime.timezone.utc).isoformat(),'complete_vpic_snapshot':False,'vehiclesdb_commit':commit,'vehiclesdb_version':manifest['version'],'scope':['Passenger Car','Motorcycle'],'limitation':'The supplied 2026-11-18 snapshot was not attached. Direct vPIC retrieval was rate-blocked after 94 car make responses. This bundled fallback is populated, but does NOT meet the requested complete vPIC snapshot coverage. See CATALOG.md.','makes':sorted(rows.values(),key=lambda x:x['name'].casefold())}
 for row in output['makes']:row['models'].sort(key=lambda x:x['name'].casefold())
 output['make_count']=len(rows);output['model_count']=sum(len(m['models']) for m in rows.values());output['type_counts']={t:sum(t in r['types'] for r in rows.values()) for t in ['Passenger Car','Motorcycle']};output['model_type_counts']={'Passenger Car':car_count,'Motorcycle':len(models)}
 tmp=ROOT/'data/catalog.new.json';tmp.write_text(json.dumps(output,ensure_ascii=False),encoding='utf-8');tmp.replace(ROOT/'data/catalog.json')
 (ROOT/'data/VEHICLESDB-ATTRIBUTION.md').write_bytes(attribution);(ROOT/'data/VEHICLESDB-SOURCES.md').write_bytes(sources)
 print({k:v for k,v in output.items() if k!='makes'})
if __name__=='__main__':main()
