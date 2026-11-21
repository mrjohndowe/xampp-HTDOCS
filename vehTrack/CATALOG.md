# Bundled offline catalog — actual provenance and coverage

## Important scope limitation

The competition text references a supplied November 18, 2026 catalog with 1,889 makes and 11,948 models. **No project files or catalog attachment were present in this workspace.** Those numbers are not claimed for this deliverable.

A direct, type-filtered NHTSA vPIC retrieval was attempted. Requests were blocked with HTTP 403 after 94 passenger-car make responses. Rather than inventing entries, labeling unfiltered truck models as cars, or delivering empty pickers, this project bundles the successfully retrieved passenger-car data plus a reproducibly pinned, complete **Motorcycle projection** from VehiclesDB.

**This fallback does not meet the competition's full-snapshot coverage requirement.** It is genuinely populated and useful offline, but should be replaced with the intended complete type-filtered snapshot before claiming competition completeness. Manual entry remains available for missing makes/models.

## Actual included counts

| Measure | Count |
|---|---:|
| Distinct makes (case-insensitive merge of matching names) | **379** |
| Make/model records | **7,543** |
| Makes with Passenger Car entries | **94** |
| Makes with Motorcycle entries | **290** |
| Makes appearing in both projections | **5** |
| Passenger Car make/model records | **1,146** |
| Motorcycle make/model records | **6,397** |

The counts are calculated from `data/catalog.json`, not estimates or minimum targets. Models with different upstream IDs remain distinct source records. `types` is provided on **every make and every model**. All entries are classified Passenger Car or Motorcycle; no unfiltered mixed-manufacturer models were loaded from vPIC's general make-only endpoint.

## Sources and retrieval

### Passenger Car

- Source: NHTSA vPIC, https://vpic.nhtsa.dot.gov/api/
- Retrieved on **November 19, 2026 America/Denver** (November 20 UTC).
- Make query: `GetMakesForVehicleType/car?format=json` (reported 195 makes at retrieval).
- Model query: `GetModelsForMakeIdYear/makeId/{id}/vehicletype/car?format=json`, without a model-year restriction.
- Only actual returned records with `VehicleTypeId = 2` and `VehicleTypeName = Passenger Car` are included.
- The 94 successful raw type-filtered responses are preserved under `data/catalog-cache/` to make the fallback reproducible. They contain no user data.
- Examples: DODGE → Dart; FORD → passenger-car models, not F-150. Changing make clears the selected model in both interfaces.
- **Coverage is incomplete:** the remaining vPIC passenger-car makes and the vPIC motorcycle retrieval did not complete. A missing make is not a claim that it has no models.
- The Passenger Car selection does not include vPIC MPV/SUV, truck, trailer, bus, low-speed, incomplete or off-road types. vPIC is a U.S.-oriented vehicle information source, **not a census of every vehicle ever manufactured worldwide**.

### Motorcycle

- Source: **Vehicle data by [VehiclesDB](https://vehiclesdb.com)**, https://github.com/vehiclesdb/vehiclesdb
- Pinned commit: `5d0eaacaeb58190cb5481ab06ddc13715dc3ed48`
- Upstream manifest version: `2026.11.12`; manifest build timestamp `2026-11-17T19:58:24Z`.
- Downloaded **2026-11-20T03:58:58Z** (November 19 America/Denver).
- Files: `catalog/motorcycle/makes.json` and `catalog/motorcycle/models.json`.
- All 290 makes and 6,397 models in that source's `kind = motorcycle` projection are included. **No car, moped, van, truck or bus projection from VehiclesDB is imported.** Source classification is retained, not independently certified as a vehicle's registration or roadworthiness status in every jurisdiction.
- License: CC BY 4.0 with upstream register attribution. See **data/VEHICLESDB-ATTRIBUTION.md** and **data/VEHICLESDB-SOURCES.md**, reproduced verbatim from the pinned commit. This app's changes reduce/project to make/model/type fields, sort entries and merge identical case-insensitive make names with the vPIC subset. VehiclesDB and the source registers do not endorse this application.

## Offline behavior

The browser's onboarding loads the local JSON directly. Authenticated add/edit forms and the Python GUI fetch the exact same bundled JSON through the PHP API. A fresh XAMPP installation needs **no outbound internet** for these pickers. Desktop use requires connection to the PHP website, not to a third-party catalog service. Make/model filtering is entirely local after loading, so there are no late per-make network responses to race. Saved strings are not constrained to the catalog and survive editing/removal from a future catalog.

## Refresh the complete vPIC projection (optional)

From the project directory with Python 3.10+:

```bat
py tools\refresh_catalog.py
```

This queries the complete current car and motorcycle make lists and the type-filtered model endpoint for every make/type, caches successful responses, calculates real counts, and writes `data/catalog.json` **atomically only if every request succeeds**. A failed refresh returns a nonzero exit status and leaves the current bundled catalog intact. Requests are retried with exponential delays. The initial attempt in this environment was blocked; do not repeatedly hammer a blocked upstream. Respect its limits, wait, or obtain the documented intended snapshot from its supplier. No external service is needed for normal app use. A future successful vPIC refresh may have different counts; update this document and the relevant attribution. Website counts are read from the installed JSON.

To reproduce the shipped fallback exactly:

```bat
py tools\import_catalog.py --commit 5d0eaacaeb58190cb5481ab06ddc13715dc3ed48
```

This retains the successful vPIC cache, fetches only the pinned Motorcycle source projection and its notices, validates the kind, and atomically replaces the output only after all downloads validate. Without `--commit`, it pins the current GitHub commit in the output. A failed download does not overwrite the local catalog.

### Import a supplied complete snapshot

Back up the existing JSON first. Convert the supplied snapshot to this documented shape and validate counts/type labels before atomically replacing `data/catalog.json`:

```json
{
  "source": "exact documented source",
  "retrieved": "ISO timestamp of actual retrieval",
  "make_count": 1,
  "model_count": 1,
  "makes": [
    {
      "id": "source-make-id",
      "name": "Actual source make",
      "types": ["Passenger Car"],
      "models": [
        {"id": "source-model-id", "name": "Actual source model", "types": ["Passenger Car"]}
      ]
    }
  ]
}
```

The shape example above is **documentation, not bundled catalog data**. Include every permitted record from the chosen snapshot, filter model types as well as makes, retain upstream licenses/notices, and run `php tests/unit.php`. Refresh both clients to load the new catalog. Never delete or rewrite saved vehicle strings merely because a refreshed catalog omits them.
