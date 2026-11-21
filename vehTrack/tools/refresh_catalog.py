"""Atomically refresh the offline vPIC Passenger Car / Motorcycle catalog.
A failed or partial refresh never replaces the bundled catalog. Python 3.10+.
"""
import concurrent.futures, datetime, json, pathlib, time, urllib.request
ROOT = pathlib.Path(__file__).resolve().parents[1]
BASE = 'https://vpic.nhtsa.dot.gov/api/vehicles/'
def get(path):
    error = None
    for attempt in range(5):
        try:
            with urllib.request.urlopen(BASE + path + ('&' if '?' in path else '?') + 'format=json', timeout=60) as r:
                data = json.load(r)
            if 'Results' not in data: raise ValueError('Missing Results')
            return data['Results']
        except Exception as exc:
            error = exc; time.sleep(2 ** attempt)
    raise RuntimeError(f'{path}: {error}')
def main():
    cache = ROOT / 'data' / 'catalog-cache'; cache.mkdir(exist_ok=True)
    entries = {}
    for kind, label in [('car', 'Passenger Car'), ('motorcycle', 'Motorcycle')]:
        for row in get('GetMakesForVehicleType/' + kind):
            entries[(int(row['MakeId']), kind)] = (row['MakeName'], label)
    def load(pair):
        (mid, kind), (name, label) = pair
        p = cache / f'{mid}-{kind}.json'
        if p.exists(): models = json.loads(p.read_text())
        else:
            models = get(f'GetModelsForMakeIdYear/makeId/{mid}/vehicletype/{kind}')
            p.write_text(json.dumps(models))
        return mid, name, label, models
    makes = {}; done = 0
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        for mid, name, label, models in pool.map(load, entries.items()):
            make = makes.setdefault(mid, {'id': mid, 'name': name, 'types': [], 'models': {}})
            make['types'].append(label)
            for row in models:
                key = int(row['Model_ID'])
                model = make['models'].setdefault(key, {'id': key, 'name': row['Model_Name'], 'types': []})
                if label not in model['types']: model['types'].append(label)
            done += 1
            if done % 100 == 0: print(f'{done}/{len(entries)} type-filtered make requests', flush=True)
    rows = []
    for make in sorted(makes.values(), key=lambda x: x['name'].casefold()):
        make['models'] = sorted(make['models'].values(), key=lambda x: x['name'].casefold()); rows.append(make)
    output = {'source': 'NHTSA vPIC', 'source_url': BASE, 'retrieved': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'complete_vpic_snapshot': True, 'scope': ['Passenger Car', 'Motorcycle'], 'method': 'GetMakesForVehicleType; GetModelsForMakeIdYear/makeId/{id}/vehicletype/{car|motorcycle}, without a model-year restriction. Only type-filtered results are included.', 'make_count': len(rows), 'model_count': sum(len(m['models']) for m in rows), 'type_counts': {t: sum(t in m['types'] for m in rows) for t in ['Passenger Car','Motorcycle']}, 'makes': rows}
    tmp = ROOT / 'data/catalog.new.json'; tmp.write_text(json.dumps(output, ensure_ascii=False), encoding='utf-8'); tmp.replace(ROOT / 'data/catalog.json')
    print({k:v for k,v in output.items() if k != 'makes'}, flush=True)
if __name__ == '__main__': main()
