# ITI CareerTrack — Database ER Diagram (v1.0 review draft)

> **How to read the diagrams**
> - Each box is a table. `PK` = primary key (the row's own ID), `FK` = foreign key (a link to another table), `UK` = must be unique.
> - Line endings: `||` = exactly one, `o|` = zero or one, `o{` = zero or many, `|{` = one or many.
>   Example: `STUDENTS ||--o{ EMPLOYMENTS` reads *"one student has zero or many employment records"*.
> - The full model has **48 tables** (attendance is additionally split into yearly partitions). To keep it readable it is shown as one **domain map** plus **five detailed diagrams**.
> - PNG copies of every diagram are in [`diagrams/`](diagrams/).
> - The exact column list, data types and constraints are in [`../database/schema.sql`](../database/schema.sql).

---

## 0. Domain map (the big picture)

```mermaid
flowchart LR
    subgraph GEO["Institutes (tenant boundary)"]
        D[districts] --> TK[talukas]
        D --> I[itis]
        I --> IT[iti_trades]
        T[trades] --> IT
        T --> TM[trade_modules]
        IT --> B[batches<br/>unit + shift]
        AY[academic_years] --> B
    end

    subgraph IAM["Identity & access"]
        U[users] --> URA[user_role_assignments<br/>role + scope + dates]
        R[roles] --> URA
        R --> RP[role_permissions] --> P[permissions]
    end

    subgraph STU["Student identity"]
        S[(students)] --> PCID[pcid_registry]
        S --> SC[student_contacts]
        S --> DUP[duplicate_candidates<br/>student_merges]
    end

    subgraph ENR["Enrolment"]
        A[admissions] --> IA[institute_affiliations]
        A --> TA[teacher_assignments]
        A --> TR[transfer_requests]
    end

    subgraph TRN["Training & exams"]
        TRR[training_records] --> ATT[attendance_records]
        TRR --> MP[module_progress]
        TRR --> AS[assessments]
        ER[exam_results] --> ERC[exam_result_components]
        ES[exam_sessions] --> ER
    end

    subgraph CAR["Career pathways"]
        AP[apprenticeships]
        EM[employments]
        BR[business_records]
        HE[higher_education_records]
        CSH[career_status_history]
        CO[companies] --> CB[company_branches]
        CL[colleges]
        V[vacancies] --> JA[job_applications]
    end

    subgraph OPS["Follow-up, verification, audit"]
        FU[follow_ups]
        VR[verification_records]
        DOC[student_documents]
        CR[correction_requests]
        CON[consents]
        AL[audit_logs]
        N[notifications]
    end

    S --> A
    I --> A
    A --> TRR
    A --> ER
    S --> AP & EM & BR & HE & CSH
    CO --> AP & EM & V
    CL --> HE
    S --> FU & VR & DOC & CR & CON
    URA -. scopes .-> I
```

---

## 1. Institutes, trades and access control

```mermaid
erDiagram
    DISTRICTS ||--o{ TALUKAS : contains
    DISTRICTS ||--o{ ITIS : contains
    TALUKAS |o--o{ ITIS : "located in"
    ITIS ||--o{ ITI_TRADES : offers
    TRADES ||--o{ ITI_TRADES : "available at"
    TRADES ||--o{ TRADE_MODULES : "syllabus"
    ITI_TRADES ||--o{ BATCHES : "runs units"
    ACADEMIC_YEARS ||--o{ BATCHES : "intake year"

    USERS ||--o{ USER_ROLE_ASSIGNMENTS : holds
    ROLES ||--o{ USER_ROLE_ASSIGNMENTS : grants
    ROLES ||--o{ ROLE_PERMISSIONS : includes
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "part of"
    DISTRICTS |o--o{ USER_ROLE_ASSIGNMENTS : "district scope"
    ITIS |o--o{ USER_ROLE_ASSIGNMENTS : "ITI scope"
    COMPANIES |o--o{ USER_ROLE_ASSIGNMENTS : "company scope"

    DISTRICTS {
        uuid id PK
        text code UK
        text name UK
        text name_mr
    }
    TALUKAS {
        uuid id PK
        uuid district_id FK
        text code UK
        text name
    }
    ITIS {
        uuid id PK
        text iti_code UK "official code"
        text name
        text ownership_type "GOVERNMENT / PRIVATE"
        uuid district_id FK
        uuid taluka_id FK "must be in same district"
        text status
    }
    TRADES {
        uuid id PK
        text trade_code UK
        text name
        text scheme "NCVT / SCVT"
        smallint duration_months
    }
    TRADE_MODULES {
        uuid id PK
        uuid trade_id FK
        text module_code "UK with trade"
        smallint sequence_no
    }
    ITI_TRADES {
        uuid id PK
        uuid iti_id FK "UK with trade_id"
        uuid trade_id FK
        smallint sanctioned_units
        smallint seats_per_unit
    }
    ACADEMIC_YEARS {
        uuid id PK
        text label UK "2027-28"
        date start_date "years never overlap"
        date end_date
    }
    BATCHES {
        uuid id PK
        uuid iti_id FK "with trade_id -> iti_trades"
        uuid trade_id FK
        uuid academic_year_id FK
        smallint unit_no
        text shift
    }
    USERS {
        uuid id PK
        citext email UK
        text mobile UK
        text password_hash "Argon2id"
        boolean mfa_enabled
        uuid student_id FK "Student / Alumni login"
        text status
    }
    ROLES {
        uuid id PK
        text code UK "PRINCIPAL, TEACHER ..."
        text scope_type "STATE/DISTRICT/ITI/COMPANY/SELF"
    }
    PERMISSIONS {
        uuid id PK
        text code UK "student.contact.view"
    }
    ROLE_PERMISSIONS {
        uuid role_id PK, FK
        uuid permission_id PK, FK
    }
    USER_ROLE_ASSIGNMENTS {
        uuid id PK
        uuid user_id FK
        uuid role_id FK
        text scope_type "must match role"
        uuid district_id FK "only for DISTRICT"
        uuid iti_id FK "only for ITI"
        uuid company_id FK "only for COMPANY"
        date valid_from
        date valid_to
        text status
    }
```

---

## 2. Student identity, PCID and enrolment

```mermaid
erDiagram
    STUDENTS ||--o{ PCID_REGISTRY : "owns (exactly 1 ACTIVE)"
    STUDENTS ||--o{ STUDENT_CONTACTS : "contact history"
    STUDENTS |o--o| USERS : "alumni login"
    STUDENTS ||--o{ DUPLICATE_CANDIDATES : "possible match"
    STUDENTS ||--o| STUDENT_MERGES : "merged into survivor"
    STUDENTS ||--o{ ADMISSIONS : "admission episodes"
    ITI_TRADES ||--o{ ADMISSIONS : "enrols in"
    ACADEMIC_YEARS ||--o{ ADMISSIONS : during
    BATCHES |o--o{ ADMISSIONS : "unit + shift"
    ADMISSIONS |o--o| ADMISSIONS : "previous_admission (transfer chain)"
    ADMISSIONS ||--|| INSTITUTE_AFFILIATIONS : "auto-created"
    ITIS ||--o{ INSTITUTE_AFFILIATIONS : "owns student for period"
    ADMISSIONS ||--o{ TEACHER_ASSIGNMENTS : "teachers for episode"
    USERS ||--o{ TEACHER_ASSIGNMENTS : teacher
    ADMISSIONS ||--o{ TRANSFER_REQUESTS : "from"
    ADMISSIONS |o--o| TRANSFER_REQUESTS : "resulting new admission"
    ITI_TRADES ||--o{ TRANSFER_REQUESTS : "to ITI + trade"

    STUDENTS {
        uuid id PK "internal, never changes"
        text first_name
        text middle_name
        text last_name
        text full_name_normalized "generated; fuzzy index"
        date date_of_birth
        text gender
        text ssc_board "UK with seat no + year"
        text ssc_seat_number
        smallint ssc_passing_year
        bytea national_id_hash UK "optional, HMAC only"
        text profile_status "ACTIVE / MERGED ..."
        uuid merged_into_student_id FK
    }
    PCID_REGISTRY {
        uuid id PK
        text pcid UK "ITIC-MH-2026-000123-7"
        uuid student_id FK
        smallint issue_year "UK with serial"
        int serial
        text status "ACTIVE / RETIRED_MERGED / VOID"
        uuid issued_for_admission_id FK
        uuid redirect_to_pcid_id FK
    }
    STUDENT_CONTACTS {
        uuid id PK
        uuid student_id FK
        text contact_type "MOBILE / EMAIL / ADDRESS ..."
        text value
        text value_normalized "for duplicate check"
        boolean is_primary
        timestamptz valid_from
        timestamptz valid_to
    }
    DUPLICATE_CANDIDATES {
        uuid id PK
        uuid student_id_a FK
        uuid student_id_b FK
        smallint match_score
        text_array match_reasons
        text status
    }
    STUDENT_MERGES {
        uuid id PK
        uuid survivor_student_id FK
        uuid merged_student_id FK, UK
        text reason
        uuid approved_by FK
    }
    ADMISSIONS {
        uuid id PK
        uuid student_id FK "max 1 live per student"
        uuid iti_id FK
        uuid trade_id FK "ITI must offer trade"
        uuid academic_year_id FK
        uuid batch_id FK "must match ITI + trade"
        text admission_type "REGULAR / TRANSFER_IN / RE_ADMISSION"
        uuid previous_admission_id FK
        date admission_date
        text status "ACCEPTED/ACTIVE/COMPLETED/TRANSFERRED_OUT..."
        date end_date
    }
    INSTITUTE_AFFILIATIONS {
        uuid id PK
        uuid student_id FK "no overlapping periods"
        uuid iti_id FK
        uuid admission_id FK, UK
        date start_date
        date end_date
        text status
    }
    TEACHER_ASSIGNMENTS {
        uuid id PK
        uuid student_id FK
        uuid teacher_user_id FK "must be active TEACHER at ITI"
        uuid iti_id FK
        uuid admission_id FK
        text assignment_role
        date start_date
        date end_date
    }
    TRANSFER_REQUESTS {
        uuid id PK
        uuid student_id FK "max 1 open per student"
        uuid from_iti_id FK
        uuid from_admission_id FK
        uuid to_iti_id FK "must differ from from_iti"
        uuid to_trade_id FK
        date requested_effective_date
        text reason
        text status "REQUESTED/ACCEPTED/REJECTED/CANCELLED"
        uuid requested_by FK
        uuid decided_by FK
        uuid new_admission_id FK
    }
```

---

## 3. Training, attendance, assessments and examinations

```mermaid
erDiagram
    ADMISSIONS ||--o{ TRAINING_RECORDS : "training episode"
    TRAINING_RECORDS ||--o{ ATTENDANCE_RECORDS : "daily"
    TRAINING_RECORDS ||--o{ MODULE_PROGRESS : "per module"
    TRADE_MODULES ||--o{ MODULE_PROGRESS : module
    TRAINING_RECORDS ||--o{ ASSESSMENTS : "internal marks"
    TRADE_MODULES |o--o{ ASSESSMENTS : "optional module"
    ADMISSIONS ||--o{ EXAM_RESULTS : "each attempt"
    EXAM_SESSIONS ||--o{ EXAM_RESULTS : "held in"
    EXAM_RESULTS ||--o{ EXAM_RESULT_COMPONENTS : "theory / practical ..."
    USERS ||--o{ ATTENDANCE_RECORDS : "marked_by"
    USERS ||--o{ ASSESSMENTS : assessor

    TRAINING_RECORDS {
        uuid id PK
        uuid student_id FK
        uuid admission_id FK
        uuid iti_id FK "consistent with admission"
        date start_date
        date expected_end_date
        date actual_end_date
        text completion_status
    }
    ATTENDANCE_RECORDS {
        bigint id PK "partitioned by year"
        date attendance_date PK
        uuid student_id FK
        uuid training_record_id FK
        uuid iti_id FK
        text session "UK with record + date"
        text status "PRESENT / ABSENT / LEAVE ..."
        uuid marked_by FK
    }
    MODULE_PROGRESS {
        uuid id PK
        uuid training_record_id FK "UK with module"
        uuid module_id FK
        numeric progress_percent "0-100"
        text status
        date completed_on
    }
    ASSESSMENTS {
        uuid id PK
        uuid training_record_id FK
        uuid module_id FK
        text assessment_type
        numeric max_marks
        numeric marks_obtained "<= max_marks"
        uuid assessor_user_id FK
        timestamptz voided_at "correction, not delete"
    }
    EXAM_SESSIONS {
        uuid id PK
        text name "AITT July 2028"
        text exam_body "NCVT / SCVT"
        text exam_type "MAIN / SUPPLEMENTARY"
        date session_month
    }
    EXAM_RESULTS {
        uuid id PK
        uuid student_id FK
        uuid admission_id FK "UK with attempt_no"
        uuid exam_session_id FK
        smallint attempt_no
        text result "PASS/FAIL/SUPPLEMENTARY/ABSENT..."
        numeric marks_obtained
        text certificate_number UK
        text verification_status
    }
    EXAM_RESULT_COMPONENTS {
        uuid id PK
        uuid exam_result_id FK
        text component "UK with result"
        numeric max_marks
        numeric marks_obtained
    }
```

---

## 4. Career pathways, employers and colleges

```mermaid
erDiagram
    STUDENTS ||--o{ APPRENTICESHIPS : undertakes
    STUDENTS ||--o{ EMPLOYMENTS : "works in (1 row per job)"
    STUDENTS ||--o{ BUSINESS_RECORDS : operates
    STUDENTS ||--o{ HIGHER_EDUCATION_RECORDS : studies
    STUDENTS ||--o{ CAREER_STATUS_HISTORY : "status timeline"
    COMPANIES ||--o{ COMPANY_BRANCHES : has
    COMPANIES ||--o{ APPRENTICESHIPS : hosts
    COMPANY_BRANCHES |o--o{ APPRENTICESHIPS : "at branch"
    COMPANIES ||--o{ EMPLOYMENTS : employs
    COMPANY_BRANCHES |o--o{ EMPLOYMENTS : "at branch"
    APPRENTICESHIPS |o--o{ EMPLOYMENTS : "absorbed after"
    COLLEGES ||--o{ HIGHER_EDUCATION_RECORDS : provides
    APPRENTICESHIPS |o--o{ CAREER_STATUS_HISTORY : evidence
    EMPLOYMENTS |o--o{ CAREER_STATUS_HISTORY : evidence
    BUSINESS_RECORDS |o--o{ CAREER_STATUS_HISTORY : evidence
    HIGHER_EDUCATION_RECORDS |o--o{ CAREER_STATUS_HISTORY : evidence
    COMPANIES ||--o{ VACANCIES : posts
    VACANCIES ||--o{ JOB_APPLICATIONS : receives
    STUDENTS ||--o{ JOB_APPLICATIONS : submits

    COMPANIES {
        uuid id PK
        text legal_name
        text cin UK
        text gstin UK
        text status
    }
    COMPANY_BRANCHES {
        uuid id PK
        uuid company_id FK
        text branch_name
        uuid district_id FK "NULL if outside MH"
        char state_code
    }
    APPRENTICESHIPS {
        uuid id PK
        uuid student_id FK "never changes"
        uuid company_id FK "never changes"
        uuid branch_id FK "must belong to company"
        text scheme
        date start_date
        date actual_end_date
        numeric stipend_amount
        text stipend_period
        text status
        text verification_status
        text source "STAFF/STUDENT/EMPLOYER"
    }
    EMPLOYMENTS {
        uuid id PK
        uuid student_id FK "never changes"
        uuid company_id FK "never changes"
        uuid branch_id FK "must belong to company"
        text designation
        text employment_type
        date joining_date
        date leaving_date
        numeric salary_amount
        text salary_period "MONTHLY/ANNUAL/DAILY/HOURLY"
        text status "ACTIVE / ENDED"
        text verification_status
    }
    BUSINESS_RECORDS {
        uuid id PK
        uuid student_id FK
        text business_name
        text registration_type "UDYAM/GST/..."
        date start_date
        text status
        text verification_status
    }
    COLLEGES {
        uuid id PK
        text name
        text university
        text college_code UK
    }
    HIGHER_EDUCATION_RECORDS {
        uuid id PK
        uuid student_id FK
        uuid college_id FK
        text course_name
        text course_level
        text study_mode
        smallint admission_year
        text status
    }
    CAREER_STATUS_HISTORY {
        uuid id PK
        uuid student_id FK "no overlapping periods"
        text status "EMPLOYED ... NOT_UPDATED, NOT_CONTACTABLE"
        date effective_from
        date effective_to "auto-closed by next status"
        text source
        text verification_status
        uuid employment_id FK "max 1 evidence link"
    }
    VACANCIES {
        uuid id PK
        uuid company_id FK
        uuid branch_id FK
        uuid trade_id FK
        text vacancy_type "JOB / APPRENTICESHIP"
        text status
    }
    JOB_APPLICATIONS {
        uuid id PK
        uuid vacancy_id FK "UK with student"
        uuid student_id FK
        boolean consent_to_share_profile
        text status
        uuid resulting_employment_id FK
    }
```

---

## 5. Follow-up, verification, documents, consent and audit

```mermaid
erDiagram
    STUDENTS ||--o{ FOLLOW_UPS : requires
    ITIS ||--o{ FOLLOW_UPS : "institute queue"
    USERS ||--o{ FOLLOW_UPS : "assigned to"
    STUDENTS ||--o{ STUDENT_DOCUMENTS : owns
    STUDENTS ||--o{ VERIFICATION_RECORDS : "verified for"
    EMPLOYMENTS |o--o{ VERIFICATION_RECORDS : target
    APPRENTICESHIPS |o--o{ VERIFICATION_RECORDS : target
    EXAM_RESULTS |o--o{ VERIFICATION_RECORDS : target
    STUDENT_DOCUMENTS |o--o{ VERIFICATION_RECORDS : "evidence"
    COMPANIES |o--o{ VERIFICATION_RECORDS : "employer confirmation"
    STUDENTS ||--o{ CORRECTION_REQUESTS : "asks to fix"
    STUDENTS ||--o{ CONSENTS : gives
    USERS ||--o{ AUDIT_LOGS : performs
    USERS ||--o{ NOTIFICATIONS : receives

    FOLLOW_UPS {
        uuid id PK
        uuid student_id FK
        uuid iti_id FK
        uuid assigned_to_user_id FK
        text reason
        date due_date
        text channel "PHONE/SMS/WHATSAPP/VISIT"
        text status
        text outcome "UPDATED/NOT_REACHABLE/..."
    }
    STUDENT_DOCUMENTS {
        uuid id PK
        uuid student_id FK
        text document_type
        text storage_key UK "private bucket key"
        text mime_type "pdf/jpeg/png"
        int size_bytes "max 10 MB"
        bytea sha256
        text malware_scan_status
        text verification_status
    }
    VERIFICATION_RECORDS {
        uuid id PK "append-only"
        uuid student_id FK
        uuid employment_id FK "exactly ONE target"
        uuid apprenticeship_id FK
        uuid exam_result_id FK
        uuid verifier_user_id FK
        uuid verifier_company_id FK
        text method
        text decision "VERIFIED / REJECTED / NEEDS_INFO"
        uuid evidence_document_id FK
        text evidence_reference
        timestamptz decided_at
    }
    CORRECTION_REQUESTS {
        uuid id PK
        uuid student_id FK
        text target_table
        uuid target_id
        jsonb proposed_changes
        text status
    }
    CONSENTS {
        uuid id PK
        uuid student_id FK
        text purpose
        boolean granted
        text policy_version
        timestamptz recorded_at
    }
    AUDIT_LOGS {
        bigint id PK "append-only"
        uuid actor_user_id
        text action
        text entity_table
        text entity_id
        uuid student_id
        uuid iti_id
        jsonb before_data
        jsonb after_data
        timestamptz occurred_at
    }
    NOTIFICATIONS {
        bigint id PK
        uuid recipient_user_id FK
        text channel
        text template_code
        text status
    }
```

> Not drawn to save space: `integration_logs` (technical log of external calls, no personal data), `pcid_year_counters` (one row per year holding the last PCID serial).
