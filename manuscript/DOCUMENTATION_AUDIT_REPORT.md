# Documentation Audit Report

This report outlines the discrepancies, grammatical errors, and outdated information found in the project's documentation (`Malinao_manuscript.pdf` and `Malinao_srs.pdf`) compared to the current state of the Digital Farmers' Produce System (DFPS) codebase.

## 1. Grammatical & Typographical Corrections

These errors were identified in the text of the PDF documents:

*   **Manuscript Figure 1 Title (Page 18):** Change "Rapid Application **Developement**" to "**Development**".
*   **Manuscript Page 46:** Change "th deployment" to "**the** deployment".
*   **Manuscript Page 46 & List of Figures:** Change "Febuary" to "**February**".
*   **SRS Page 9 (List of Figures):** Change "Febuary" to "**February**".

## 2. Alignment with Program Features (Functional Updates)

The documentation currently lags behind some of the recent technical improvements.

### A. Gantt Chart Removal
*   **Discrepancy:** The documents still list and show a "Gantt Chart" as a system module (e.g., Figure 24 in the Manuscript).
*   **Correction:** Remove Figure 24 and all references to the internal Gantt chart module. Update the "List of Figures" to reflect this removal. The project now focuses on a streamlined dashboard.

### B. Multi-Image Support
*   **Discrepancy:** Documentation refers to "image" or "photo" in the singular.
*   **Correction (SRS FR-03):** Update the requirement to state: "The system shall allow farmers to upload **up to 10 photos** per product listing." Update the Manuscript (Page 10, 34, 43) to reflect this batch upload and carousel display feature.

### C. Language Support Consistency
*   **Discrepancy:** The SRS uses "Cebuano," while the Manuscript uses "Bisaya."
*   **Correction:** Standardize on "**Bisaya (Cebuano)**" to match the user interface label currently implemented in `universal_header.php`.

### D. Profile Picture Management
*   **Addition:** Add a mention in the "User Management" or "UI/UX" sections about the new **Profile Picture Management** features, including the use of Bootstrap Icons (`bi-person-circle`) as default placeholders and the ability to remove/reset profile photos.

## 3. Template Leftovers (Critical Mismatches)

The SRS contains several "placeholder" examples from a different system (likely a fraud detection template) that need to be replaced:

*   **SRS FR-01 Acceptance Criteria (Page 6):**
    *   **Current:** "When a transaction is flagged, an alert appears on the dashboard in 5 seconds."
    *   **Corrected:** "User is redirected to their role-specific dashboard (Farmer, Buyer, or DA) upon successful login; invalid credentials trigger a clear error message."
*   **SRS Traceability Matrix (Page 15, Section 8.2):**
    *   **Current:** FR-02 is listed as "Fraud Detection Algorithm Spec."
    *   **Corrected:** FR-02 should point to the "Product Categorization Module" or "Category Management Design."

## 4. Technical Merit Additions (Recommended)

To reflect the high-quality engineering work done recently, it is recommended to add these to the **Non-Functional Requirements** or **Implementation** sections:

*   **Environment Portability:** Mention the implementation of robust fallback helper functions (`dfps_fetch_all()` and `dfps_fetch_assoc()`) to ensure the application runs seamlessly on servers lacking the `mysqlnd` PHP driver.
*   **Database Self-Healing:** Mention the automatic schema update system (`includes/db.php`) that ensures the database structure remains consistent across different deployments without requiring manual SQL execution.

## Next Steps

Since the source documents are likely in Word or LaTeX format, these corrections must be applied to the source files before exporting updated PDF versions to the `manuscript/` folder.
