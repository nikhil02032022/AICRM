# User Manual — Group V: Analytics, Dashboards and Reporting

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Sprint:** 4 | **Group:** V
**Last Updated:** 2026-04-23

---

## Overview

The Analytics, Dashboards and Reporting module gives counsellors, managers, and directors actionable insight into admissions performance. Access to specific dashboards and reports depends on your role.

---

## Role-Based Access (AR-007)

| Feature | Counsellor | Manager | Director / Admin |
|---|---|---|---|
| View own analytics | ✓ | ✓ | ✓ |
| Institution-wide dashboard | — | ✓ | ✓ |
| Executive KPI dashboard | — | — | ✓ |
| Run standard reports | ✓ | ✓ | ✓ |
| Create/manage custom reports | — | ✓ | ✓ |
| Export reports | ✓ | ✓ | ✓ |
| Schedule reports | — | ✓ | ✓ |

**Important:** All data is automatically scoped to your institution. You will never see data from another institution.

---

## Counsellor Performance Dashboard (AR-002)

**Who can access:** All CRM users with `crm.analytics.view` permission (counsellors, managers, directors)

**URL:** `/crm/analytics/dashboards/counsellor`

### How to Use

1. Navigate to **Analytics → Counsellor Dashboard** in the sidebar.
2. The **My Performance** section shows your own metrics for the selected period:
   - **My Leads** — total leads assigned to you
   - **Converted** — leads that reached enrolled/converted status
   - **Conversion Rate** — percentage of leads converted
   - **Tasks Assigned / Completed** — total tasks and completed count
   - **Avg. First Response Time** — average hours from lead creation to first contact
3. **Managers and Directors** see an additional **Team Performance Ranking** table listing all counsellors sorted by leads volume, with colour-coded performance badges (High / Medium / Low based on conversion rate).
4. Apply date filters to compare performance across different periods.

### Performance Badges

| Badge | Conversion Rate |
|---|---|
| High (green) | ≥ 30% |
| Medium (yellow) | 15% – 29% |
| Low (red) | < 15% |

---

## Institution Admissions Dashboard (AR-001)

**Who can access:** Managers, Directors, Institution Admins (`crm.analytics.institution` permission required)

**URL:** `/crm/analytics/dashboards/institution`

### How to Use

1. Navigate to **Analytics → Institution Dashboard** in the sidebar.
2. The dashboard loads with the current month's data by default.
3. **KPI Tiles (top row):** Total Leads, Applications, Offers, Enrolments, and Revenue — institution-wide totals for the selected period.
4. **By Programme chart (bar):** Shows lead and application counts broken down per programme.
5. **By Source chart (doughnut):** Shows the proportion of leads by acquisition source (e.g. Website, Agent, Walk-in).
6. **Programme Breakdown table:** Scrollable table with Leads, Applications, and Conversion % per programme.

### Filtering by Date Range

- At the top of the page, enter a **From** and **To** date, then click **Apply**.
- Click **Clear** to reset to all-time data.
- Date filter applies to all KPI tiles and charts simultaneously.

### Notes

- Data is scoped to your institution automatically — no cross-institution data is ever shown.
- Counsellors do not have access to this dashboard (use the Counsellor Dashboard instead).

---

*(Additional sections will be added as each Req ID is implemented)*
