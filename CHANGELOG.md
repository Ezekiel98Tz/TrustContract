# Changelog

All notable changes for TrustContract on 2026-01-21.

## Added
- Notifications page with unread badge and mark-read actions.
- Counterparty full reviews pages and links from Create/Show.
- Printable contract view with audit details (IP/device) and Save as PDF.
- Quick contract templates (plain/formal) in create flow to auto-draft terms.

## Changed
- Create Contract flow: reliable submit, action-required banner for prerequisites.
- Contract page: success panel with “Sign Now” and “View My Contracts”.
- Currency-specific high-value thresholds (USD/EUR/TZS) for verification checks.
- Contracts Show layout: Parties and Terms side-by-side; Review moved below.
- Sidebar collapse visuals and icon navigation; improved spacing on hamburger.
- Top navbar: avatar + dropdown (Profile, Log Out) and cleaned header area.
- “Download PDF” uses white-paper printable style with auto Save-as-PDF.

## Removed
- Inline PDF preview page; standardized on Printable for viewing/export.

## Infrastructure
- Config toggles for notifications mail channel.
- Currency thresholds in config for policy-driven high-value checks.

## Notes
- For server-side PDF generation performance, install dompdf and pre-generate PDFs on final signature; current client PDF is reliable and styled. 
