# Changelog

All notable changes to `dhl-express-rest` will be documented in this file.

## 0.8.0 - 2026-09-30

### Added

- Added a typed commercial-invoice line path with directed string commodity
  codes, caller line numbers, total line weights, `PCS`, optional tax-paid
  state, and supplied pre-calculated totals.
- Added explicit exporter details and validated generic invoice charges.

### Changed

- Kept the legacy positional `LineItem` constructor and its `BOX` default.
- Fixed test bootstrap execution when the PHP binary path contains spaces.

## 0.7.0 - 2026-07-06

### Added

- Added explicit `guzzlehttp/guzzle ^7.0` requirement.
- Added required `x-version: 3.3.1` header on all requests.
- Added validation for `Tracking::multi()`: 1 to 200 tracking numbers per multi-tracking call.
- Added optional `provinceCode` on `Address`.
- Added a contract test suite validating generated payloads against the bundled MyDHL API 3.3.1 OpenAPI spec.
- Added `bin/build-spec.php` to sanitise DHL's tab-containing published YAML before contract validation.
- Added `DHL::landedCost()->retrieve(array $payload)` and `LandedCostResponse` helpers for the MyDHL landed-cost endpoint.

### Changed

- Widened `nesbot/carbon` constraint to `^2.63|^3.0` for Carbon 3 and Laravel 12 consumers.
- `Address` now accepts all six MyDHL API 3.3.1 `typeCode` values: `business`, `direct_consumer`, `government`, `other`, `private`, and `reseller`.
- `Rates::retrieve()` uses the creator's declared value and currency when set. Existing callers that do not set a declared value keep the previous default of `100 GBP`.
- DHL 5xx responses now throw `Booni3\DhlExpressRest\Exceptions\ResponseException` instead of leaking raw Guzzle `ServerException`.

### Fixed

- Fixed the production base URI, which was previously an unusable placeholder.
- Fixed `AddressException` and `ShipmentException` loading under strict PSR-4 autoloading. FQCNs are unchanged.
- Removed accidental dependency on Laravel's `now()` and `today()` helpers.
- Fixed a fatal error when DHL returned a non-JSON error body.
- Fixed error handling when a DHL error payload omits the `status` key.
- Fixed `Tracking::multi()`, which previously sent malformed query parameters such as `0[shipmentTrackingNumber]=...`; it now sends repeated `shipmentTrackingNumber` parameters per the spec.
- Fixed response objects so missing optional response fields no longer error.
- Fixed rates sorting for products without price or delivery estimates.
- Fixed shipment export declaration line item numbers to start at `1`, matching the MyDHL schema.
- Fixed shipment export declaration generation so repeated payload builds do not increment line item numbers.
- Fixed shipment line items so they no longer emit the unsupported `priceCurrency` field. The constructor parameter remains for source compatibility.

### Upgrade Notes

- No breaking public API changes are intended; this should be a drop-in upgrade for 0.6.x consumers.
- Composer constraints pinned to `^0.6` will not install `0.7.0`; update consuming apps to `^0.7` when they are ready to take this release.
- Review any app code catching `GuzzleHttp\Exception\ServerException` around DHL calls. Package-level `ResponseException` now covers DHL 5xx responses.
- `Tracking::multi()` now sends the correct MyDHL query shape. If an app was compensating for the previous malformed query or handling odd DHL results from that request, re-test that path.
- Requirements are now explicit: PHP `^7.4|^8.0`, Guzzle `^7.0`, and Carbon `^2.63|^3.0`.

## 0.6.2 and earlier

- Historical releases before this changelog was maintained.
