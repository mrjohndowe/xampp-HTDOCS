# Offline vehicle catalog

**Source**: U.S. National Highway Traffic Safety Administration, [Vehicle Product Information Catalog (vPIC) API](https://vpic.nhtsa.dot.gov/api/). Public government vehicle-registration and VIN manufacturer submissions; not an exhaustive worldwide inventory. The snapshot is used as an identification aid, not a specification or compliance database.

**Retrieved**: 2026-11-20 (UTC). No catalog file accompanied the starter workspace, so this submission bundles a newly retrieved snapshot. The question's 2026-11-18 figures describe a different snapshot, not these files.

| vPIC classification | Make IDs | Make/model pairs in this classification |
| --- | ---: | ---: |
| Passenger Car (`VehicleTypeId=2`) | 195 | 1,385 |
| Motorcycle (`VehicleTypeId=1`) | 1,703 | 10,273 |
| Distinct across both categories | **1,891** | **11,658** |

Seven make IDs occur in both types. The snapshot has 23 typed makes without model rows; manual model entry remains available. These counts use unique `(make ID, model ID)` pairs, not unique spellings. Each model pair belongs only to the vehicle-type section(s) actually returned for that model, independently of the make's other types.

Raw makes come from `GetMakesForVehicleType/{passenger%20car|motorcycle}?format=json`. The typed models are the complete CSV response from `GetModelsForMakeYear/make/%25/vehicletype/{passenger%20car|motorcycle}?format=csv` (NHTSA's `%` partial-name wildcard, no model-year restriction). Bundled data is the intersection of those two exact type classifications. Models returned by the agency under Truck, Bus, Trailer, MPV, Incomplete, Off Road or Low Speed Vehicle are not added under those classifications. The source's classifications are authoritative for this snapshot; marketing names alone are not used as a classifier.

Run `py tools\refresh_catalog.py` from the application folder to refresh when online. All four responses are validated and the old catalog is replaced atomically only after success. An outage, partial/short response, or inconsistent type leaves the existing snapshot intact. See README for the local CSV/JSON import option. The website and desktop read the same bundled snapshot through the PHP server (no online lookups during forms or onboarding).
