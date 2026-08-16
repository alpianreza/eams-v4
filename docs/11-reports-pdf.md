# 11 — Reports, PDF & Export

## A. Library PDF: `App\Libraries\EamsPdf`

- **Engine:** mPDF (`mpdf/mpdf`) sebagai primary bila class ada → fallback **Dompdf** (komentar: "paket yang sudah terbukti terpasang di server, dipakai PDAM & Thermal Imaging").
- `export($type, $data)`: type → view template:

| type | View | Kertas | Dipakai oleh |
|---|---|---|---|
| `single` | `pdf/single_item` | A4 portrait | ExportPdfController@single |
| `daily` | `pdf/recap_daily` | A4-L | recap daily |
| `daily_toilet` | `pdf/recap_daily_toilet` | A4-L | recap item_type 52 |
| `weekly` | `pdf/recap_weekly` | A4 portrait | recap weekly |
| `recap_period` | `pdf/recap_periodic` | A4 portrait | (tersedia; tidak ter-route langsung) |
| `recap_year_item` | `pdf/recap_item_yearly` | A4 portrait | recap monthly (setahun per item) |
| `batch_form` | `pdf/batch_form` (+`pdf/batch_partials/*`) | A4-L, margin 7/6mm | Print Center batch |
| `questionnaire_response` | `pdf/questionnaire_response` | A4 portrait | respon kuesioner |
| `attachment_ng` | `pdf/attachment_ng` | A4 portrait | lampiran temuan (template tersedia) |

- **Branding:** `applyCompanySettings` membaca `app_settings` (company_name/address/logo/document_footer/signatory) dengan fallback `PT. YOUNGHYUN STAR` + `assets/images/company/logo.png`; `replaceLegacyBranding` mengganti string nama PT lama di template.
- Footer mPDF: `…|{PAGENO}|` (wajib 3 segmen — komentar kode); Dompdf: `page_text` "Hal. {PAGE_NUM}".
- Output: mPDF `Output($filename,'I')` (inline), Dompdf `stream(..., Attachment=false)`; `while (ob_get_level()) ob_end_clean();` sebelum output.

## B. PDF langsung via Dompdf (tanpa EamsPdf)

| Fitur | Controller | View | Format |
|---|---|---|---|
| PDAM Water PDF | `PdamWaterController@exportPdf` | `pdam_water/export_pdf` | A4 landscape |
| PDAM Water Boiler PDF | `PdamWaterBoilerController@exportPdf` | `pdam_water_boiler/export_pdf` | A4 landscape |
| Thermal Imaging PDF | `ThermalImagingController@pdf` | `compliance/thermal_imaging/print` | A4 portrait |

## C. Export Excel (PhpSpreadsheet)

| Fitur | Controller@method | Isi | Filename |
|---|---|---|---|
| Boiler bulanan | `BoilerFuelController@export` | harian: SUM polybag/kg; hari libur merah (FFDD9999); total | `Laporan_Boiler_Y_m.xlsx` |
| IPAL bulanan | `IpalController@export` | start/stop/pemakaian/ket per hari; libur merah (FFFFCCCC) | `IPAL_Report_Y_m.xlsx` |
| PDAM bulanan | `PdamWaterController@exportExcel` | jam/meter/ket/status per hari (Terisi/Belum/Libur) | `PDAM_Water_Report_Y_m.xlsx` |
| PDAM Boiler bulanan | `PdamWaterBoilerController@exportExcel` | idem | `PDAM_Water_Boiler_Report_Y_m.xlsx` |
| Hasil kuesioner | `ComplianceQuestionnaireController@exportExcel` | respon × pertanyaan; header biru 1F5FBF; freeze+filter | `Hasil-{slug}.xlsx` |
| Progress user | `ProgressController@export` | **CSV** (User, Total, Done, Pending, Late, Progress%) | `progress-Y-m.csv` |

## D. Print Center (`compliance/print`)

- `index`: pilih mode; `item`/`itemPreview`: pilih N inventory + tahun + bulan → preview per item; `inventoryByType`: partial daftar inventory per item type.
- `batch`/`batchPreview`: PDF kolektif per item type + bulan:
  - Data: semua inventory item type (urut asset_code; smoke/heat detector urut `TRIM(specific_area), asset_code`).
  - Matrix: monthly → status per (inventory, pertanyaan); weekly → per (inventory, pertanyaan, W1-4); daily → per (inventory, tanggal) + daftar hari + flag offday.
  - Agregasi status multi-log: **worst-case** `not_ok > ok > na` (`aggregateBatchStatus`).
  - Findings: log `not_ok` bulan itu + foto (hanya bila file ada di `uploads/checklist/`).
  - Layout per item (judul/kolom/grup/tanda tangan hard-coded) — lihat 10 §6.

## E. Report screen (`compliance/report`)

- Filter berjenjang: kategori → item type (dari inventory yang ada) → inventory; periode tahun (+bulan utk daily/weekly).
- `loadAjax` → `_table` (memilih partial `_daily/_weekly/_monthly`); findings + checker per periode; prev/next item.

## F. Business rule terkait output

- **CONFIRMED:** status symbol PDF single: ok→✓, not_ok→✗, na→- (`EamsPdf::mapStatus`).
- **CONFIRMED:** checklist PDF hanya seakurat `checklist_logs`; periode kosong tampil kosong (tidak ada "auto late" di PDF — late hanya di layar/home/progress).
- **CONFIRMED:** hari libur & weekend ditandai merah di export utilitas & tidak dihitung sebagai hari wajib daily.
- **CONFIRMED:** Export PDF hanya butuh login (`auth`); `PdfAccessFilter` (admin-only) tidak terpasang → potensi ketidaksesuaian akses (14/15).
- **INFERRED:** mPDF dipasang kemudian sebagai pengganti Dompdf utama; komentar fallback menunjukkan Dompdf "yang terbukti di server".

## G. Route & parameter export (ringkas)

```
GET export/pdf/single/{inventoryId}/{periodKey}   (auth)
GET export/pdf/recap/{inventoryId}/{year}/{month} (auth)
GET compliance/print/batch/preview?item_type_id=&month=&year= (auth)
GET pdam-water/export-excel|export-pdf?year=&month= / monthpicker= (auth; role admin/compliance/office)
GET pdam-water-boiler/export-* (idem)
GET boiler/export?year=&month=
GET ipal/export?year=&month=
GET compliance/questionnaires/{id}/excel
GET compliance/questionnaires/response/{id}/pdf
GET compliance/progress/export?month=Y-m (CSV)
GET compliance/thermal-imaging/{id}/pdf
```
