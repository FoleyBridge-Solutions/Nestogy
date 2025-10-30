--
-- PostgreSQL database dump
--

\restrict o0WKrvcOIrSR7abqcc4IzEk1SJVfnhJjFzP9AAUC16JpR8RXn502IIwbchx5yt0

-- Dumped from database version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_holds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_holds (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint,
    hold_reference character varying(255),
    name character varying(255) NOT NULL,
    hold_type character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_by integer,
    grace_period_hours integer DEFAULT 0 NOT NULL,
    grace_period_expires_at timestamp(0) without time zone,
    resulted_in_payment boolean DEFAULT false NOT NULL,
    payment_amount_received numeric(10,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: account_holds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_holds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_holds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_holds_id_seq OWNED BY public.account_holds.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.accounts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency_code character varying(3) NOT NULL,
    notes text,
    type integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    plaid_id character varying(255)
);


--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    batch_uuid uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: analytics_snapshots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.analytics_snapshots (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: analytics_snapshots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.analytics_snapshots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: analytics_snapshots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.analytics_snapshots_id_seq OWNED BY public.analytics_snapshots.id;


--
-- Name: asset_depreciations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asset_depreciations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    asset_id bigint NOT NULL,
    purchase_cost numeric(12,2) NOT NULL,
    residual_value numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    useful_life_years integer NOT NULL,
    method character varying(255) DEFAULT 'straight_line'::character varying NOT NULL,
    annual_depreciation numeric(12,2) NOT NULL,
    accumulated_depreciation numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    current_book_value numeric(12,2) NOT NULL,
    depreciation_start_date date NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT asset_depreciations_method_check CHECK (((method)::text = ANY (ARRAY[('straight_line'::character varying)::text, ('declining_balance'::character varying)::text, ('sum_of_years'::character varying)::text])))
);


--
-- Name: asset_depreciations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.asset_depreciations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asset_depreciations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.asset_depreciations_id_seq OWNED BY public.asset_depreciations.id;


--
-- Name: asset_maintenance; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asset_maintenance (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    asset_id bigint NOT NULL,
    maintenance_type character varying(255) NOT NULL,
    description text NOT NULL,
    scheduled_date timestamp(0) without time zone NOT NULL,
    completed_date timestamp(0) without time zone,
    cost numeric(10,2),
    notes text,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    technician_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT asset_maintenance_status_check CHECK (((status)::text = ANY (ARRAY[('scheduled'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: asset_maintenance_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.asset_maintenance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asset_maintenance_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.asset_maintenance_id_seq OWNED BY public.asset_maintenance.id;


--
-- Name: asset_warranties; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asset_warranties (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    asset_id bigint NOT NULL,
    warranty_provider character varying(255) NOT NULL,
    warranty_number character varying(255),
    start_date date NOT NULL,
    end_date date NOT NULL,
    type character varying(255) DEFAULT 'manufacturer'::character varying NOT NULL,
    coverage_details text,
    cost numeric(10,2),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT asset_warranties_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('expired'::character varying)::text, ('claimed'::character varying)::text]))),
    CONSTRAINT asset_warranties_type_check CHECK (((type)::text = ANY (ARRAY[('manufacturer'::character varying)::text, ('extended'::character varying)::text, ('service_contract'::character varying)::text])))
);


--
-- Name: asset_warranties_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.asset_warranties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asset_warranties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.asset_warranties_id_seq OWNED BY public.asset_warranties.id;


--
-- Name: assets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.assets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    make character varying(255) NOT NULL,
    model character varying(255),
    serial character varying(255),
    os character varying(255),
    ip character varying(45),
    nat_ip character varying(255),
    mac character varying(17),
    uri character varying(500),
    uri_2 character varying(500),
    status character varying(255),
    purchase_date date,
    warranty_expire date,
    install_date date,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    next_maintenance_date date,
    support_level character varying(50),
    auto_assigned_support boolean DEFAULT false NOT NULL,
    support_assigned_at timestamp(0) without time zone,
    support_assigned_by bigint,
    support_last_evaluated_at timestamp(0) without time zone,
    support_evaluation_rules json,
    support_notes text,
    asset_tag character varying(255),
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    vendor_id bigint,
    location_id bigint,
    contact_id bigint,
    network_id bigint,
    client_id bigint NOT NULL,
    rmm_id character varying(255),
    support_status character varying(255) DEFAULT 'unsupported'::character varying NOT NULL,
    supporting_contract_id bigint,
    supporting_schedule_id bigint,
    CONSTRAINT assets_support_status_check CHECK (((support_status)::text = ANY (ARRAY[('supported'::character varying)::text, ('unsupported'::character varying)::text, ('pending_assignment'::character varying)::text, ('excluded'::character varying)::text])))
);


--
-- Name: COLUMN assets.next_maintenance_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.next_maintenance_date IS 'Date when the next scheduled maintenance is due for this asset';


--
-- Name: COLUMN assets.support_level; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_level IS 'Level of support: basic, standard, premium, enterprise, etc.';


--
-- Name: COLUMN assets.auto_assigned_support; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.auto_assigned_support IS 'Whether support was automatically assigned vs manually assigned';


--
-- Name: COLUMN assets.support_assigned_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_assigned_at IS 'When support was assigned to this asset';


--
-- Name: COLUMN assets.support_assigned_by; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_assigned_by IS 'User who assigned support to this asset';


--
-- Name: COLUMN assets.support_last_evaluated_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_last_evaluated_at IS 'When support status was last evaluated';


--
-- Name: COLUMN assets.support_evaluation_rules; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_evaluation_rules IS 'Rules used to determine support status';


--
-- Name: COLUMN assets.support_notes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_notes IS 'Notes about support assignment or exclusion reasons';


--
-- Name: COLUMN assets.support_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.support_status IS 'Whether this asset is covered by a support contract';


--
-- Name: COLUMN assets.supporting_contract_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.supporting_contract_id IS 'Contract that provides support for this asset';


--
-- Name: COLUMN assets.supporting_schedule_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.assets.supporting_schedule_id IS 'Contract schedule (Schedule A) that defines asset support';


--
-- Name: assets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.assets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: assets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.assets_id_seq OWNED BY public.assets.id;


--
-- Name: attribution_touchpoints; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attribution_touchpoints (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    lead_id bigint,
    contact_id bigint,
    client_id bigint,
    touchpoint_type character varying(255) NOT NULL,
    campaign_id bigint,
    source character varying(255),
    medium character varying(255),
    campaign character varying(255),
    content character varying(255),
    term character varying(255),
    page_url character varying(255),
    referrer_url character varying(255),
    ip_address character varying(255),
    user_agent text,
    metadata json,
    touched_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: attribution_touchpoints_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attribution_touchpoints_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attribution_touchpoints_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attribution_touchpoints_id_seq OWNED BY public.attribution_touchpoints.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    company_id bigint,
    event_type character varying(50) NOT NULL,
    model_type character varying(255),
    model_id bigint,
    action character varying(255) NOT NULL,
    old_values json,
    new_values json,
    metadata json,
    ip_address character varying(45),
    user_agent character varying(255),
    session_id character varying(255),
    request_method character varying(10),
    request_url character varying(255),
    request_headers json,
    request_body json,
    response_status integer,
    execution_time numeric(10,3),
    severity character varying(20) DEFAULT 'info'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: auto_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auto_payments (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    client_id bigint,
    payment_method_id bigint,
    type character varying(255) DEFAULT 'invoice_auto_pay'::character varying NOT NULL,
    trigger_type character varying(255) DEFAULT 'invoice_due'::character varying NOT NULL,
    trigger_days_offset integer DEFAULT 0 NOT NULL,
    trigger_time time(0) without time zone DEFAULT '09:00:00'::time without time zone NOT NULL,
    currency_code character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    next_processing_date timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    activated_at timestamp(0) without time zone,
    deactivated_at timestamp(0) without time zone
);


--
-- Name: auto_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auto_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auto_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auto_payments_id_seq OWNED BY public.auto_payments.id;


--
-- Name: bouncer_abilities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bouncer_abilities (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    title character varying(255),
    entity_id bigint,
    entity_type character varying(255),
    only_owned boolean DEFAULT false NOT NULL,
    options json,
    scope integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: bouncer_abilities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bouncer_abilities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bouncer_abilities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bouncer_abilities_id_seq OWNED BY public.bouncer_abilities.id;


--
-- Name: bouncer_assigned_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bouncer_assigned_roles (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    entity_id bigint NOT NULL,
    entity_type character varying(255) NOT NULL,
    restricted_to_id bigint,
    restricted_to_type character varying(255),
    scope integer
);


--
-- Name: bouncer_assigned_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bouncer_assigned_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bouncer_assigned_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bouncer_assigned_roles_id_seq OWNED BY public.bouncer_assigned_roles.id;


--
-- Name: bouncer_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bouncer_permissions (
    id bigint NOT NULL,
    ability_id bigint NOT NULL,
    entity_id bigint,
    entity_type character varying(255),
    forbidden boolean DEFAULT false NOT NULL,
    scope integer
);


--
-- Name: bouncer_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bouncer_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bouncer_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bouncer_permissions_id_seq OWNED BY public.bouncer_permissions.id;


--
-- Name: bouncer_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bouncer_roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    title character varying(255),
    scope integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: bouncer_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bouncer_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bouncer_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bouncer_roles_id_seq OWNED BY public.bouncer_roles.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: campaign_enrollments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaign_enrollments (
    id bigint NOT NULL,
    campaign_id bigint NOT NULL,
    lead_id bigint,
    contact_id bigint,
    status character varying(255) DEFAULT 'enrolled'::character varying NOT NULL,
    current_step integer DEFAULT 0 NOT NULL,
    enrolled_at timestamp(0) without time zone NOT NULL,
    last_activity_at timestamp(0) without time zone,
    next_send_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    emails_sent integer DEFAULT 0 NOT NULL,
    emails_opened integer DEFAULT 0 NOT NULL,
    emails_clicked integer DEFAULT 0 NOT NULL,
    converted boolean DEFAULT false NOT NULL,
    converted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT campaign_enrollments_status_check CHECK (((status)::text = ANY (ARRAY[('enrolled'::character varying)::text, ('active'::character varying)::text, ('completed'::character varying)::text, ('paused'::character varying)::text, ('unsubscribed'::character varying)::text, ('bounced'::character varying)::text])))
);


--
-- Name: campaign_enrollments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.campaign_enrollments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: campaign_enrollments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.campaign_enrollments_id_seq OWNED BY public.campaign_enrollments.id;


--
-- Name: campaign_sequences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaign_sequences (
    id bigint NOT NULL,
    campaign_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    step_number integer NOT NULL,
    delay_days integer DEFAULT 0 NOT NULL,
    delay_hours integer DEFAULT 0 NOT NULL,
    subject_line character varying(255) NOT NULL,
    email_template text NOT NULL,
    email_text text,
    send_conditions json,
    skip_conditions json,
    is_active boolean DEFAULT true NOT NULL,
    send_time time(0) without time zone DEFAULT '09:00:00'::time without time zone NOT NULL,
    send_days json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: campaign_sequences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.campaign_sequences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: campaign_sequences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.campaign_sequences_id_seq OWNED BY public.campaign_sequences.id;


--
-- Name: cash_flow_projections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cash_flow_projections (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: cash_flow_projections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cash_flow_projections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cash_flow_projections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cash_flow_projections_id_seq OWNED BY public.cash_flow_projections.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type jsonb NOT NULL,
    code character varying(50),
    slug character varying(255),
    description text,
    color character varying(255),
    icon character varying(255),
    parent_id bigint,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: client_addresses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_addresses (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    type character varying(255) DEFAULT 'billing'::character varying NOT NULL,
    address character varying(255) NOT NULL,
    address2 character varying(255),
    city character varying(255) NOT NULL,
    state character varying(255) NOT NULL,
    zip character varying(255) NOT NULL,
    country character varying(2) DEFAULT 'US'::character varying NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_addresses_type_check CHECK (((type)::text = ANY (ARRAY[('billing'::character varying)::text, ('shipping'::character varying)::text, ('service'::character varying)::text, ('other'::character varying)::text])))
);


--
-- Name: client_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_addresses_id_seq OWNED BY public.client_addresses.id;


--
-- Name: client_calendar_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_calendar_events (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    start_time timestamp(0) without time zone NOT NULL,
    end_time timestamp(0) without time zone NOT NULL,
    all_day boolean DEFAULT false NOT NULL,
    type character varying(255) DEFAULT 'other'::character varying NOT NULL,
    attendees json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_calendar_events_type_check CHECK (((type)::text = ANY (ARRAY[('maintenance'::character varying)::text, ('meeting'::character varying)::text, ('project'::character varying)::text, ('other'::character varying)::text])))
);


--
-- Name: client_calendar_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_calendar_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_calendar_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_calendar_events_id_seq OWNED BY public.client_calendar_events.id;


--
-- Name: client_certificates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_certificates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    domain character varying(255) NOT NULL,
    issuer character varying(255),
    issue_date date,
    expiry_date date NOT NULL,
    type character varying(255) DEFAULT 'ssl'::character varying NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_certificates_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('expired'::character varying)::text, ('pending'::character varying)::text, ('revoked'::character varying)::text]))),
    CONSTRAINT client_certificates_type_check CHECK (((type)::text = ANY (ARRAY[('ssl'::character varying)::text, ('wildcard'::character varying)::text, ('ev'::character varying)::text, ('dv'::character varying)::text, ('ov'::character varying)::text])))
);


--
-- Name: client_certificates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_certificates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_certificates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_certificates_id_seq OWNED BY public.client_certificates.id;


--
-- Name: client_contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_contacts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    title character varying(255),
    email character varying(255),
    phone character varying(255),
    mobile character varying(255),
    is_primary boolean DEFAULT false NOT NULL,
    is_billing boolean DEFAULT false NOT NULL,
    is_technical boolean DEFAULT false NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_contacts_id_seq OWNED BY public.client_contacts.id;


--
-- Name: client_credentials; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_credentials (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    service_name character varying(255) NOT NULL,
    username character varying(255) NOT NULL,
    password text NOT NULL,
    url character varying(255),
    notes text,
    additional_fields json,
    is_shared boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_credentials_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_credentials_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_credentials_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_credentials_id_seq OWNED BY public.client_credentials.id;


--
-- Name: client_credit_applications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_credit_applications (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_credit_id bigint NOT NULL,
    applicable_type character varying(255) NOT NULL,
    applicable_id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    applied_date date NOT NULL,
    applied_by bigint,
    is_active boolean DEFAULT true NOT NULL,
    unapplied_at timestamp(0) without time zone,
    unapplied_by bigint,
    unapplication_reason text,
    notes text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: client_credit_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_credit_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_credit_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_credit_applications_id_seq OWNED BY public.client_credit_applications.id;


--
-- Name: client_credits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_credits (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    source_type character varying(255) NOT NULL,
    source_id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    used_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    available_amount numeric(10,2) NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    credit_date date NOT NULL,
    expiry_date date,
    depleted_at timestamp(0) without time zone,
    voided_at timestamp(0) without time zone,
    reference_number character varying(255) NOT NULL,
    reason text,
    notes text,
    metadata json,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT client_credits_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('depleted'::character varying)::text, ('expired'::character varying)::text, ('voided'::character varying)::text]))),
    CONSTRAINT client_credits_type_check CHECK (((type)::text = ANY (ARRAY[('overpayment'::character varying)::text, ('prepayment'::character varying)::text, ('credit_note'::character varying)::text, ('promotional'::character varying)::text, ('goodwill'::character varying)::text, ('refund_credit'::character varying)::text, ('adjustment'::character varying)::text])))
);


--
-- Name: client_credits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_credits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_credits_id_seq OWNED BY public.client_credits.id;


--
-- Name: client_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_documents (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255),
    file_path character varying(255) NOT NULL,
    mime_type character varying(255),
    file_size bigint,
    description text,
    tags json,
    is_confidential boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    storage_path character varying(255),
    storage_disk character varying(255) DEFAULT 'local'::character varying NOT NULL,
    version integer DEFAULT 1 NOT NULL,
    is_current_version boolean DEFAULT true NOT NULL
);


--
-- Name: client_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_documents_id_seq OWNED BY public.client_documents.id;


--
-- Name: client_domains; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_domains (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    domain character varying(255) NOT NULL,
    registrar character varying(255),
    registration_date date,
    expiry_date date,
    auto_renew boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    dns_records json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_domains_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('expired'::character varying)::text, ('pending'::character varying)::text, ('suspended'::character varying)::text])))
);


--
-- Name: client_domains_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_domains_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_domains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_domains_id_seq OWNED BY public.client_domains.id;


--
-- Name: client_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_files (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    original_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    file_size bigint NOT NULL,
    category character varying(255),
    description text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_files_id_seq OWNED BY public.client_files.id;


--
-- Name: client_it_documentation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_it_documentation (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint,
    authored_by bigint,
    name character varying(255) NOT NULL,
    description text,
    it_category character varying(255),
    system_references json,
    ip_addresses json,
    software_versions json,
    compliance_requirements json,
    review_schedule character varying(255),
    last_reviewed_at timestamp(0) without time zone,
    next_review_at timestamp(0) without time zone,
    access_level character varying(255),
    procedure_steps json,
    network_diagram json,
    related_entities json,
    tags json,
    version integer DEFAULT 1 NOT NULL,
    parent_document_id bigint,
    file_path character varying(255),
    original_filename character varying(255),
    filename character varying(255),
    file_size bigint,
    mime_type character varying(255),
    file_hash character varying(255),
    last_accessed_at timestamp(0) without time zone,
    access_count integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    enabled_tabs json,
    tab_configuration json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    effective_date date,
    expiry_date date,
    template_used character varying(255),
    ports json,
    api_endpoints json,
    ssl_certificates json,
    dns_entries json,
    firewall_rules json,
    vpn_settings json,
    hardware_references json,
    environment_variables json,
    procedure_diagram json,
    rollback_procedures json,
    prerequisites json,
    data_classification character varying(255),
    encryption_required boolean DEFAULT false NOT NULL,
    audit_requirements json,
    security_controls json,
    external_resources json,
    vendor_contacts json,
    support_contracts json,
    test_cases json,
    validation_checklist json,
    performance_benchmarks json,
    health_checks json,
    automation_scripts json,
    integrations json,
    webhooks json,
    scheduled_tasks json,
    uptime_requirement numeric(5,2),
    rto integer,
    rpo integer,
    performance_metrics json,
    alert_thresholds json,
    escalation_paths json,
    change_summary text,
    change_log json,
    requires_technical_review boolean DEFAULT false NOT NULL,
    requires_management_approval boolean DEFAULT false NOT NULL,
    approval_history json,
    review_comments json,
    custom_fields json,
    documentation_completeness integer DEFAULT 0 NOT NULL,
    is_template boolean DEFAULT false NOT NULL,
    template_category character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: client_it_documentation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_it_documentation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_it_documentation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_it_documentation_id_seq OWNED BY public.client_it_documentation.id;


--
-- Name: client_licenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_licenses (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    software_name character varying(255) NOT NULL,
    license_key character varying(255) NOT NULL,
    version character varying(255),
    seats integer DEFAULT 1 NOT NULL,
    purchase_date date,
    expiry_date date,
    cost numeric(10,2),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_licenses_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('expired'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: client_licenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_licenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_licenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_licenses_id_seq OWNED BY public.client_licenses.id;


--
-- Name: client_networks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_networks (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    network_address character varying(255) NOT NULL,
    subnet_mask character varying(255) NOT NULL,
    gateway character varying(255),
    dns_primary character varying(255),
    dns_secondary character varying(255),
    vlan_id integer,
    description text,
    type character varying(255) DEFAULT 'lan'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_networks_type_check CHECK (((type)::text = ANY (ARRAY[('lan'::character varying)::text, ('wan'::character varying)::text, ('dmz'::character varying)::text, ('guest'::character varying)::text, ('management'::character varying)::text])))
);


--
-- Name: client_networks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_networks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_networks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_networks_id_seq OWNED BY public.client_networks.id;


--
-- Name: client_portal_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_portal_sessions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint,
    session_token character varying(255) NOT NULL,
    refresh_token character varying(255) NOT NULL,
    device_id character varying(255),
    device_name character varying(255),
    device_type character varying(255),
    browser_name character varying(255),
    browser_version character varying(255),
    os_name character varying(255),
    os_version character varying(255),
    ip_address character varying(255),
    user_agent text,
    location_data json,
    is_mobile boolean DEFAULT false NOT NULL,
    is_trusted_device boolean DEFAULT false NOT NULL,
    two_factor_verified boolean DEFAULT false NOT NULL,
    two_factor_method character varying(255),
    two_factor_verified_at timestamp(0) without time zone,
    last_activity_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone NOT NULL,
    refresh_expires_at timestamp(0) without time zone NOT NULL,
    session_data json,
    security_flags json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    revocation_reason character varying(255),
    revoked_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: client_portal_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_portal_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_portal_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_portal_sessions_id_seq OWNED BY public.client_portal_sessions.id;


--
-- Name: client_portal_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_portal_users (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint,
    name character varying(255) NOT NULL,
    email character varying(255),
    password character varying(255),
    role character varying(255) DEFAULT 'viewer'::character varying NOT NULL,
    session_timeout_minutes integer DEFAULT 30 NOT NULL,
    notification_preferences json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: client_portal_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_portal_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_portal_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_portal_users_id_seq OWNED BY public.client_portal_users.id;


--
-- Name: client_quotes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_quotes (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    quote_number character varying(255) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    tax_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(10,2) NOT NULL,
    quote_date date NOT NULL,
    expiry_date date,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    line_items json NOT NULL,
    notes text,
    terms text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_quotes_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('sent'::character varying)::text, ('accepted'::character varying)::text, ('rejected'::character varying)::text, ('expired'::character varying)::text])))
);


--
-- Name: client_quotes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_quotes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_quotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_quotes_id_seq OWNED BY public.client_quotes.id;


--
-- Name: client_racks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_racks (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    location character varying(255),
    units integer DEFAULT 42 NOT NULL,
    description text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_racks_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('inactive'::character varying)::text, ('maintenance'::character varying)::text])))
);


--
-- Name: client_racks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_racks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_racks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_racks_id_seq OWNED BY public.client_racks.id;


--
-- Name: client_recurring_invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_recurring_invoices (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    invoice_number character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    start_date date NOT NULL,
    end_date date,
    next_invoice_date date NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    line_items json NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_recurring_invoices_frequency_check CHECK (((frequency)::text = ANY (ARRAY[('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('semi-annually'::character varying)::text, ('annually'::character varying)::text]))),
    CONSTRAINT client_recurring_invoices_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('paused'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: client_recurring_invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_recurring_invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_recurring_invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_recurring_invoices_id_seq OWNED BY public.client_recurring_invoices.id;


--
-- Name: client_services; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_services (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    service_type character varying(255) DEFAULT 'other'::character varying NOT NULL,
    monthly_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    start_date date NOT NULL,
    end_date date,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    service_hours json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    contract_id bigint,
    product_id bigint,
    category character varying(255),
    billing_cycle character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    setup_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_contract_value numeric(10,2),
    currency character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    auto_renewal boolean DEFAULT false NOT NULL,
    contract_terms text,
    sla_terms text,
    service_level character varying(255),
    priority_level character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    assigned_technician bigint,
    backup_technician bigint,
    escalation_contact character varying(255),
    response_time integer,
    resolution_time integer,
    availability_target numeric(5,2),
    performance_metrics json,
    monitoring_enabled boolean DEFAULT false NOT NULL,
    backup_schedule character varying(255),
    maintenance_schedule character varying(255),
    last_review_date date,
    next_review_date date,
    client_satisfaction integer,
    notes text,
    tags json,
    deleted_at timestamp(0) without time zone,
    provisioning_status character varying(255),
    provisioned_at timestamp(0) without time zone,
    activated_at timestamp(0) without time zone,
    suspended_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    cancellation_reason text,
    cancellation_fee numeric(10,2),
    renewal_date date,
    renewal_count integer DEFAULT 0 NOT NULL,
    last_renewed_at timestamp(0) without time zone,
    health_score integer,
    last_health_check_at timestamp(0) without time zone,
    sla_breaches_count integer DEFAULT 0 NOT NULL,
    last_sla_breach_at timestamp(0) without time zone,
    recurring_billing_id bigint,
    actual_monthly_revenue numeric(10,2)
);


--
-- Name: client_services_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_services_id_seq OWNED BY public.client_services.id;


--
-- Name: client_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_tags (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    tag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_tags_id_seq OWNED BY public.client_tags.id;


--
-- Name: client_trips; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_trips (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    purpose character varying(255) NOT NULL,
    description text,
    start_time timestamp(0) without time zone NOT NULL,
    end_time timestamp(0) without time zone,
    mileage numeric(8,2),
    expense_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'planned'::character varying NOT NULL,
    expenses json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_trips_status_check CHECK (((status)::text = ANY (ARRAY[('planned'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: client_trips_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_trips_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_trips_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_trips_id_seq OWNED BY public.client_trips.id;


--
-- Name: client_vendors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_vendors (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    contact_person character varying(255),
    email character varying(255),
    phone character varying(255),
    address text,
    account_number character varying(255),
    notes text,
    relationship character varying(255) DEFAULT 'vendor'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_vendors_relationship_check CHECK (((relationship)::text = ANY (ARRAY[('vendor'::character varying)::text, ('supplier'::character varying)::text, ('partner'::character varying)::text, ('contractor'::character varying)::text])))
);


--
-- Name: client_vendors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_vendors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_vendors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_vendors_id_seq OWNED BY public.client_vendors.id;


--
-- Name: clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clients (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    lead boolean DEFAULT false NOT NULL,
    name character varying(255) NOT NULL,
    company_name character varying(255),
    type character varying(255),
    email character varying(255) NOT NULL,
    phone character varying(255),
    address text,
    city character varying(255),
    state character varying(255),
    zip_code character varying(255),
    country character varying(255) DEFAULT 'US'::character varying NOT NULL,
    website character varying(255),
    referral character varying(255),
    rate numeric(15,2),
    currency_code character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    net_terms integer DEFAULT 30 NOT NULL,
    tax_id_number character varying(255),
    rmm_id integer,
    notes text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    hourly_rate numeric(8,2),
    billing_contact character varying(255),
    technical_contact character varying(255),
    custom_fields json,
    contract_start_date timestamp(0) without time zone,
    contract_end_date timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sla_id bigint,
    custom_standard_rate numeric(10,2),
    custom_after_hours_rate numeric(10,2),
    custom_emergency_rate numeric(10,2),
    custom_weekend_rate numeric(10,2),
    custom_holiday_rate numeric(10,2),
    custom_after_hours_multiplier numeric(5,2),
    custom_emergency_multiplier numeric(5,2),
    custom_weekend_multiplier numeric(5,2),
    custom_holiday_multiplier numeric(5,2),
    custom_rate_calculation_method character varying(255),
    custom_minimum_billing_increment numeric(5,2),
    custom_time_rounding_method character varying(255),
    use_custom_rates boolean DEFAULT false NOT NULL,
    company_link_id bigint,
    stripe_customer_id character varying(255),
    stripe_subscription_id character varying(255),
    subscription_status character varying(255) DEFAULT 'trialing'::character varying NOT NULL,
    subscription_plan_id bigint,
    trial_ends_at timestamp(0) without time zone,
    next_billing_date timestamp(0) without time zone,
    subscription_started_at timestamp(0) without time zone,
    subscription_canceled_at timestamp(0) without time zone,
    current_user_count integer DEFAULT 0 NOT NULL,
    industry character varying(255),
    employee_count integer,
    deleted_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    created_by bigint,
    CONSTRAINT clients_custom_rate_calculation_method_check CHECK (((custom_rate_calculation_method)::text = ANY (ARRAY[('fixed_rates'::character varying)::text, ('multipliers'::character varying)::text]))),
    CONSTRAINT clients_custom_time_rounding_method_check CHECK (((custom_time_rounding_method)::text = ANY (ARRAY[('none'::character varying)::text, ('up'::character varying)::text, ('down'::character varying)::text, ('nearest'::character varying)::text]))),
    CONSTRAINT clients_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('inactive'::character varying)::text, ('suspended'::character varying)::text])))
);


--
-- Name: clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clients_id_seq OWNED BY public.clients.id;


--
-- Name: collection_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collection_notes (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: collection_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collection_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collection_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collection_notes_id_seq OWNED BY public.collection_notes.id;


--
-- Name: communication_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.communication_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    user_id bigint NOT NULL,
    contact_id bigint,
    type character varying(255) NOT NULL,
    channel character varying(255) NOT NULL,
    contact_name character varying(255),
    contact_email character varying(255),
    contact_phone character varying(255),
    subject character varying(255) NOT NULL,
    notes text NOT NULL,
    follow_up_required boolean DEFAULT false NOT NULL,
    follow_up_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: communication_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.communication_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: communication_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.communication_logs_id_seq OWNED BY public.communication_logs.id;


--
-- Name: companies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.companies (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    address character varying(255),
    city character varying(255),
    state character varying(255),
    zip character varying(255),
    country character varying(255),
    phone character varying(255),
    email character varying(255),
    website character varying(255),
    logo character varying(255),
    locale character varying(255),
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    hourly_rate_config json,
    default_standard_rate numeric(10,2) DEFAULT '150'::numeric NOT NULL,
    default_after_hours_rate numeric(10,2) DEFAULT '225'::numeric NOT NULL,
    default_emergency_rate numeric(10,2) DEFAULT '300'::numeric NOT NULL,
    default_weekend_rate numeric(10,2) DEFAULT '200'::numeric NOT NULL,
    default_holiday_rate numeric(10,2) DEFAULT '250'::numeric NOT NULL,
    after_hours_multiplier numeric(5,2) DEFAULT 1.5 NOT NULL,
    emergency_multiplier numeric(5,2) DEFAULT '2'::numeric NOT NULL,
    weekend_multiplier numeric(5,2) DEFAULT 1.5 NOT NULL,
    holiday_multiplier numeric(5,2) DEFAULT '2'::numeric NOT NULL,
    rate_calculation_method character varying(255) DEFAULT 'fixed_rates'::character varying NOT NULL,
    minimum_billing_increment numeric(5,2) DEFAULT 0.25 NOT NULL,
    time_rounding_method character varying(255) DEFAULT 'nearest'::character varying NOT NULL,
    parent_company_id bigint,
    company_type character varying(255) DEFAULT 'root'::character varying NOT NULL,
    organizational_level integer DEFAULT 0 NOT NULL,
    subsidiary_settings json,
    access_level character varying(255) DEFAULT 'full'::character varying NOT NULL,
    billing_type character varying(255) DEFAULT 'independent'::character varying NOT NULL,
    billing_parent_id bigint,
    can_create_subsidiaries boolean DEFAULT false NOT NULL,
    max_subsidiary_depth integer DEFAULT 3 NOT NULL,
    inherited_permissions json,
    is_active boolean DEFAULT true NOT NULL,
    suspended_at timestamp(0) without time zone,
    client_record_id bigint,
    suspension_reason character varying(255),
    email_provider_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    email_provider_config json,
    size character varying(255),
    employee_count integer,
    branding json,
    company_info json,
    social_links json,
    CONSTRAINT companies_access_level_check CHECK (((access_level)::text = ANY (ARRAY[('full'::character varying)::text, ('limited'::character varying)::text, ('read_only'::character varying)::text]))),
    CONSTRAINT companies_billing_type_check CHECK (((billing_type)::text = ANY (ARRAY[('independent'::character varying)::text, ('parent_billed'::character varying)::text, ('shared'::character varying)::text]))),
    CONSTRAINT companies_company_type_check CHECK (((company_type)::text = ANY (ARRAY[('root'::character varying)::text, ('subsidiary'::character varying)::text, ('division'::character varying)::text]))),
    CONSTRAINT companies_email_provider_type_check CHECK (((email_provider_type)::text = ANY (ARRAY[('manual'::character varying)::text, ('microsoft365'::character varying)::text, ('google_workspace'::character varying)::text, ('exchange'::character varying)::text, ('custom_oauth'::character varying)::text]))),
    CONSTRAINT companies_rate_calculation_method_check CHECK (((rate_calculation_method)::text = ANY (ARRAY[('fixed_rates'::character varying)::text, ('multipliers'::character varying)::text]))),
    CONSTRAINT companies_time_rounding_method_check CHECK (((time_rounding_method)::text = ANY (ARRAY[('none'::character varying)::text, ('up'::character varying)::text, ('down'::character varying)::text, ('nearest'::character varying)::text])))
);


--
-- Name: COLUMN companies.size; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.companies.size IS 'solo, small, medium, large, enterprise';


--
-- Name: companies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.companies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: companies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.companies_id_seq OWNED BY public.companies.id;


--
-- Name: company_customizations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_customizations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    customizations json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: company_customizations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_customizations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_customizations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_customizations_id_seq OWNED BY public.company_customizations.id;


--
-- Name: company_hierarchies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_hierarchies (
    id bigint NOT NULL,
    ancestor_id bigint NOT NULL,
    descendant_id bigint NOT NULL,
    depth integer DEFAULT 1 NOT NULL,
    path character varying(1000),
    path_names text,
    relationship_type character varying(255) DEFAULT 'subsidiary'::character varying NOT NULL,
    relationship_metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT company_hierarchies_relationship_type_check CHECK (((relationship_type)::text = ANY (ARRAY[('parent_child'::character varying)::text, ('division'::character varying)::text, ('branch'::character varying)::text, ('subsidiary'::character varying)::text])))
);


--
-- Name: company_hierarchies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_hierarchies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_hierarchies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_hierarchies_id_seq OWNED BY public.company_hierarchies.id;


--
-- Name: company_mail_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_mail_settings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    driver character varying(255) DEFAULT 'smtp'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    smtp_host character varying(255),
    smtp_port integer,
    smtp_encryption character varying(255),
    smtp_username character varying(255),
    smtp_password text,
    smtp_timeout integer DEFAULT 30 NOT NULL,
    ses_key character varying(255),
    ses_secret text,
    ses_region character varying(255) DEFAULT 'us-east-1'::character varying NOT NULL,
    mailgun_domain character varying(255),
    mailgun_secret text,
    mailgun_endpoint character varying(255) DEFAULT 'api.mailgun.net'::character varying NOT NULL,
    postmark_token text,
    sendgrid_api_key text,
    from_email character varying(255) NOT NULL,
    from_name character varying(255) NOT NULL,
    reply_to_email character varying(255),
    reply_to_name character varying(255),
    rate_limit_per_minute integer DEFAULT 30 NOT NULL,
    rate_limit_per_hour integer DEFAULT 500 NOT NULL,
    rate_limit_per_day integer DEFAULT 5000 NOT NULL,
    track_opens boolean DEFAULT true NOT NULL,
    track_clicks boolean DEFAULT true NOT NULL,
    auto_retry_failed boolean DEFAULT true NOT NULL,
    max_retry_attempts integer DEFAULT 3 NOT NULL,
    last_test_at timestamp(0) without time zone,
    last_test_successful boolean,
    last_test_error text,
    fallback_config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    api_key text,
    api_secret text,
    api_domain character varying(255),
    reply_to character varying(255),
    CONSTRAINT company_mail_settings_driver_check CHECK (((driver)::text = ANY (ARRAY[('smtp'::character varying)::text, ('ses'::character varying)::text, ('mailgun'::character varying)::text, ('postmark'::character varying)::text, ('sendgrid'::character varying)::text, ('log'::character varying)::text]))),
    CONSTRAINT company_mail_settings_smtp_encryption_check CHECK (((smtp_encryption)::text = ANY (ARRAY[('tls'::character varying)::text, ('ssl'::character varying)::text, ('none'::character varying)::text])))
);


--
-- Name: company_mail_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_mail_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_mail_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_mail_settings_id_seq OWNED BY public.company_mail_settings.id;


--
-- Name: company_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_subscriptions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    subscription_plan_id bigint,
    status character varying(255) DEFAULT 'trialing'::character varying NOT NULL,
    max_users integer DEFAULT 2 NOT NULL,
    current_user_count integer DEFAULT 0 NOT NULL,
    monthly_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    stripe_subscription_id character varying(255),
    stripe_customer_id character varying(255),
    trial_ends_at timestamp(0) without time zone,
    current_period_start timestamp(0) without time zone,
    current_period_end timestamp(0) without time zone,
    canceled_at timestamp(0) without time zone,
    suspended_at timestamp(0) without time zone,
    grace_period_ends_at timestamp(0) without time zone,
    features json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT company_subscriptions_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('trialing'::character varying)::text, ('past_due'::character varying)::text, ('canceled'::character varying)::text, ('suspended'::character varying)::text, ('expired'::character varying)::text])))
);


--
-- Name: company_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_subscriptions_id_seq OWNED BY public.company_subscriptions.id;


--
-- Name: compliance_checks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.compliance_checks (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    compliance_requirement_id bigint,
    check_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    findings text,
    recommendations json,
    evidence_documents json,
    checked_by bigint,
    checked_at timestamp(0) without time zone,
    next_check_date timestamp(0) without time zone,
    compliance_score numeric(5,2),
    risk_level character varying(255) DEFAULT 'low'::character varying NOT NULL,
    metadata json
);


--
-- Name: compliance_checks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.compliance_checks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: compliance_checks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.compliance_checks_id_seq OWNED BY public.compliance_checks.id;


--
-- Name: compliance_requirements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.compliance_requirements (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    last_checked_at timestamp(0) without time zone
);


--
-- Name: compliance_requirements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.compliance_requirements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: compliance_requirements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.compliance_requirements_id_seq OWNED BY public.compliance_requirements.id;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    title character varying(255),
    email character varying(255),
    phone character varying(255),
    extension character varying(255),
    mobile character varying(255),
    photo character varying(255),
    pin character varying(255),
    notes text,
    auth_method character varying(255),
    password_hash character varying(255),
    password_reset_token character varying(255),
    token_expire timestamp(0) without time zone,
    "primary" boolean DEFAULT false NOT NULL,
    important boolean DEFAULT false NOT NULL,
    billing boolean DEFAULT false NOT NULL,
    technical boolean DEFAULT false NOT NULL,
    has_portal_access boolean DEFAULT false NOT NULL,
    portal_permissions json,
    last_login_at timestamp(0) without time zone,
    last_login_ip character varying(45),
    login_count integer DEFAULT 0 NOT NULL,
    failed_login_count integer DEFAULT 0 NOT NULL,
    locked_until timestamp(0) without time zone,
    email_verified_at timestamp(0) without time zone,
    remember_token character varying(100),
    password_changed_at timestamp(0) without time zone,
    must_change_password boolean DEFAULT false NOT NULL,
    session_timeout_minutes integer DEFAULT 30 NOT NULL,
    allowed_ip_addresses json,
    department character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    preferred_contact_method character varying(50) DEFAULT 'email'::character varying,
    best_time_to_contact character varying(50) DEFAULT 'anytime'::character varying,
    timezone character varying(100),
    language character varying(50) DEFAULT 'en'::character varying,
    do_not_disturb boolean DEFAULT false NOT NULL,
    marketing_opt_in boolean DEFAULT false NOT NULL,
    linkedin_url character varying(255),
    assistant_name character varying(255),
    assistant_email character varying(255),
    assistant_phone character varying(50),
    reports_to_id bigint,
    work_schedule text,
    professional_bio text,
    office_location_id bigint,
    is_emergency_contact boolean DEFAULT false NOT NULL,
    is_after_hours_contact boolean DEFAULT false NOT NULL,
    out_of_office_start date,
    out_of_office_end date,
    website character varying(255),
    twitter_handle character varying(100),
    facebook_url character varying(255),
    instagram_handle character varying(100),
    company_blog character varying(255),
    role character varying(255),
    invitation_sent_at timestamp(0) without time zone,
    invitation_expires_at timestamp(0) without time zone,
    invitation_accepted_at timestamp(0) without time zone,
    invitation_sent_by bigint,
    invitation_status character varying(255),
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    client_id bigint NOT NULL,
    location_id bigint,
    vendor_id bigint,
    invitation_token character varying(64),
    CONSTRAINT contacts_invitation_status_check CHECK (((invitation_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('sent'::character varying)::text, ('accepted'::character varying)::text, ('expired'::character varying)::text, ('revoked'::character varying)::text])))
);


--
-- Name: COLUMN contacts.invitation_sent_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_sent_at IS 'When the invitation was sent';


--
-- Name: COLUMN contacts.invitation_expires_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_expires_at IS 'When the invitation expires';


--
-- Name: COLUMN contacts.invitation_accepted_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_accepted_at IS 'When the invitation was accepted';


--
-- Name: COLUMN contacts.invitation_sent_by; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_sent_by IS 'User ID who sent the invitation';


--
-- Name: COLUMN contacts.invitation_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_status IS 'Current status of the invitation';


--
-- Name: COLUMN contacts.invitation_token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.invitation_token IS 'Unique token for portal invitation';


--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: contract_action_buttons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_action_buttons (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    label character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    icon character varying(50),
    button_class character varying(100) DEFAULT 'btn btn-primary'::character varying NOT NULL,
    action_type character varying(50) NOT NULL,
    action_config json NOT NULL,
    visibility_conditions json,
    permissions json,
    confirmation_message text,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_action_buttons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_action_buttons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_action_buttons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_action_buttons_id_seq OWNED BY public.contract_action_buttons.id;


--
-- Name: contract_amendments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_amendments (
    id bigint NOT NULL,
    contract_id bigint NOT NULL,
    company_id bigint NOT NULL,
    amendment_number integer NOT NULL,
    amendment_type character varying(255) NOT NULL,
    changes json NOT NULL,
    original_values json,
    reason text NOT NULL,
    effective_date date NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    applied_at timestamp(0) without time zone,
    applied_by bigint,
    created_by bigint NOT NULL,
    approval_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT contract_amendments_amendment_type_check CHECK (((amendment_type)::text = ANY (ARRAY[('renewal'::character varying)::text, ('pricing'::character varying)::text, ('term'::character varying)::text, ('sla'::character varying)::text, ('scope'::character varying)::text, ('general'::character varying)::text]))),
    CONSTRAINT contract_amendments_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('applied'::character varying)::text, ('rejected'::character varying)::text])))
);


--
-- Name: contract_amendments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_amendments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_amendments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_amendments_id_seq OWNED BY public.contract_amendments.id;


--
-- Name: contract_approvals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_approvals (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    approval_type character varying(255) NOT NULL,
    approval_level character varying(255),
    approval_order integer DEFAULT 1 NOT NULL,
    approver_id bigint NOT NULL,
    approver_role character varying(255),
    delegated_to_id bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    requested_at timestamp(0) without time zone NOT NULL,
    due_date timestamp(0) without time zone,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    comments text,
    conditions text,
    rejection_reason text,
    can_resubmit boolean DEFAULT true NOT NULL,
    amount_limit numeric(10,2),
    amount_exceeded boolean DEFAULT false NOT NULL,
    notification_sent_at timestamp(0) without time zone,
    reminder_sent_at timestamp(0) without time zone,
    reminder_count integer DEFAULT 0 NOT NULL,
    escalated_at timestamp(0) without time zone,
    escalated_to_id bigint,
    required_documents json,
    all_documents_received boolean DEFAULT false NOT NULL,
    checklist json,
    approval_method character varying(255),
    ip_address character varying(45),
    user_agent text,
    audit_trail json,
    depends_on_approval_id bigint,
    can_approve_parallel boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: contract_approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_approvals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_approvals_id_seq OWNED BY public.contract_approvals.id;


--
-- Name: contract_asset_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_asset_assignments (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    asset_id bigint NOT NULL,
    assigned_services json,
    service_pricing json,
    billing_rate numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    billing_frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    service_configuration json,
    monitoring_settings json,
    maintenance_schedule json,
    backup_configuration json,
    base_monthly_rate numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    additional_service_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    pricing_modifiers json,
    billing_rules json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    start_date date NOT NULL,
    end_date date,
    last_billed_at timestamp(0) without time zone,
    next_billing_date date,
    auto_assigned boolean DEFAULT false NOT NULL,
    assignment_rules json,
    automation_triggers json,
    last_service_update timestamp(0) without time zone,
    usage_metrics json,
    current_month_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    billing_history json,
    sla_requirements json,
    compliance_settings json,
    security_requirements json,
    assignment_notes text,
    metadata json,
    assigned_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contract_asset_assignments_billing_frequency_check CHECK (((billing_frequency)::text = ANY (ARRAY[('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('annually'::character varying)::text, ('one_time'::character varying)::text]))),
    CONSTRAINT contract_asset_assignments_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('suspended'::character varying)::text, ('terminated'::character varying)::text, ('pending'::character varying)::text])))
);


--
-- Name: contract_asset_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_asset_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_asset_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_asset_assignments_id_seq OWNED BY public.contract_asset_assignments.id;


--
-- Name: contract_billing_calculations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_billing_calculations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    billing_period_start date NOT NULL,
    billing_period_end date NOT NULL,
    billing_type character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    period_description character varying(255),
    base_contract_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    fixed_monthly_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    base_charges_breakdown json,
    total_assets integer DEFAULT 0 NOT NULL,
    workstation_count integer DEFAULT 0 NOT NULL,
    server_count integer DEFAULT 0 NOT NULL,
    network_device_count integer DEFAULT 0 NOT NULL,
    mobile_device_count integer DEFAULT 0 NOT NULL,
    asset_counts_by_type json,
    asset_billing_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    asset_billing_breakdown json,
    total_contacts integer DEFAULT 0 NOT NULL,
    basic_access_contacts integer DEFAULT 0 NOT NULL,
    standard_access_contacts integer DEFAULT 0 NOT NULL,
    premium_access_contacts integer DEFAULT 0 NOT NULL,
    admin_access_contacts integer DEFAULT 0 NOT NULL,
    contact_access_breakdown json,
    contact_billing_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    contact_billing_breakdown json,
    total_tickets_created integer DEFAULT 0 NOT NULL,
    total_support_hours numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    total_incidents_resolved integer DEFAULT 0 NOT NULL,
    usage_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    usage_breakdown json,
    service_charges json,
    monitoring_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    backup_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    security_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    maintenance_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    additional_service_charges json,
    discounts_applied numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    surcharges_applied numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    pricing_adjustments json,
    tax_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tax_rate numeric(5,4) DEFAULT '0'::numeric NOT NULL,
    subtotal_before_tax numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    currency_code character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    calculation_method character varying(255) DEFAULT 'automatic'::character varying NOT NULL,
    calculation_rules json,
    formula_applied json,
    line_items json,
    calculation_log json,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    calculated_at timestamp(0) without time zone,
    reviewed_at timestamp(0) without time zone,
    approved_at timestamp(0) without time zone,
    invoiced_at timestamp(0) without time zone,
    invoice_id bigint,
    invoice_number character varying(255),
    auto_invoice boolean DEFAULT true NOT NULL,
    invoice_due_date date,
    previous_period_amount numeric(10,2),
    amount_variance numeric(10,2),
    variance_percentage numeric(5,2),
    variance_analysis json,
    projected_next_period numeric(10,2),
    forecasting_data json,
    trend_analysis json,
    has_disputes boolean DEFAULT false NOT NULL,
    dispute_details json,
    disputed_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    adjustments_made json,
    calculation_duration_ms integer,
    performance_metrics json,
    calculation_notes text,
    calculated_by bigint,
    reviewed_by bigint,
    approved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contract_billing_calculations_billing_type_check CHECK (((billing_type)::text = ANY (ARRAY[('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('annually'::character varying)::text, ('custom'::character varying)::text, ('one_time'::character varying)::text]))),
    CONSTRAINT contract_billing_calculations_calculation_method_check CHECK (((calculation_method)::text = ANY (ARRAY[('manual'::character varying)::text, ('automatic'::character varying)::text, ('scheduled'::character varying)::text, ('triggered'::character varying)::text]))),
    CONSTRAINT contract_billing_calculations_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('calculated'::character varying)::text, ('reviewed'::character varying)::text, ('approved'::character varying)::text, ('invoiced'::character varying)::text, ('disputed'::character varying)::text])))
);


--
-- Name: contract_billing_calculations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_billing_calculations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_billing_calculations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_billing_calculations_id_seq OWNED BY public.contract_billing_calculations.id;


--
-- Name: contract_billing_model_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_billing_model_definitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    calculator_class character varying(255) NOT NULL,
    config json,
    default_rates json,
    field_requirements json,
    validation_rules json,
    supports_assets boolean DEFAULT false NOT NULL,
    supports_contacts boolean DEFAULT false NOT NULL,
    supports_usage boolean DEFAULT false NOT NULL,
    supports_tiers boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_billing_model_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_billing_model_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_billing_model_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_billing_model_definitions_id_seq OWNED BY public.contract_billing_model_definitions.id;


--
-- Name: contract_clauses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_clauses (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    category character varying(255) NOT NULL,
    clause_type character varying(255) DEFAULT 'required'::character varying NOT NULL,
    content text NOT NULL,
    variables json,
    conditions json,
    description text,
    sort_order integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    version character varying(20) DEFAULT '1.0'::character varying NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    is_required boolean DEFAULT false NOT NULL,
    applicable_contract_types json,
    metadata json,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contract_clauses_clause_type_check CHECK (((clause_type)::text = ANY (ARRAY[('required'::character varying)::text, ('conditional'::character varying)::text, ('optional'::character varying)::text]))),
    CONSTRAINT contract_clauses_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('inactive'::character varying)::text, ('archived'::character varying)::text])))
);


--
-- Name: contract_clauses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_clauses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_clauses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_clauses_id_seq OWNED BY public.contract_clauses.id;


--
-- Name: contract_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_comments (
    id bigint NOT NULL,
    contract_id bigint NOT NULL,
    negotiation_id bigint,
    version_id bigint,
    content text NOT NULL,
    comment_type character varying(255) DEFAULT 'general'::character varying NOT NULL,
    section character varying(255),
    context json,
    parent_id bigint,
    thread_position integer DEFAULT 0 NOT NULL,
    is_internal boolean DEFAULT true NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    requires_response boolean DEFAULT false NOT NULL,
    response_due timestamp(0) without time zone,
    mentions json,
    attachments json,
    user_id bigint NOT NULL,
    author_type character varying(255) DEFAULT 'internal'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_comments_id_seq OWNED BY public.contract_comments.id;


--
-- Name: contract_component_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_component_assignments (
    id bigint NOT NULL,
    contract_id bigint NOT NULL,
    component_id bigint NOT NULL,
    configuration json NOT NULL,
    pricing_override json,
    variable_values json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    assigned_by bigint,
    assigned_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_component_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_component_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_component_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_component_assignments_id_seq OWNED BY public.contract_component_assignments.id;


--
-- Name: contract_components; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_components (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255) NOT NULL,
    component_type character varying(255) NOT NULL,
    configuration json NOT NULL,
    pricing_model json,
    dependencies json,
    template_content text,
    variables json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_components_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_components_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_components_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_components_id_seq OWNED BY public.contract_components.id;


--
-- Name: contract_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_configurations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    configuration json,
    metadata json,
    is_active boolean DEFAULT true NOT NULL,
    version character varying(20) DEFAULT '1.0'::character varying NOT NULL,
    description text,
    activated_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: contract_configurations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_configurations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_configurations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_configurations_id_seq OWNED BY public.contract_configurations.id;


--
-- Name: contract_contact_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_contact_assignments (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    contact_id bigint NOT NULL,
    access_level character varying(255) DEFAULT 'basic'::character varying NOT NULL,
    access_tier_name character varying(255),
    assigned_permissions json,
    service_entitlements json,
    has_portal_access boolean DEFAULT true NOT NULL,
    can_create_tickets boolean DEFAULT true NOT NULL,
    can_view_all_tickets boolean DEFAULT false NOT NULL,
    can_view_assets boolean DEFAULT false NOT NULL,
    can_view_invoices boolean DEFAULT false NOT NULL,
    can_download_files boolean DEFAULT false NOT NULL,
    max_tickets_per_month integer DEFAULT '-1'::integer NOT NULL,
    max_support_hours_per_month integer DEFAULT '-1'::integer NOT NULL,
    allowed_ticket_types json,
    restricted_features json,
    billing_rate numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    billing_frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    per_ticket_rate numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    pricing_modifiers json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    start_date date NOT NULL,
    end_date date,
    last_billed_at timestamp(0) without time zone,
    next_billing_date date,
    current_month_tickets integer DEFAULT 0 NOT NULL,
    current_month_support_hours numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    current_month_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    last_access_date date,
    last_login_at timestamp(0) without time zone,
    total_tickets_created integer DEFAULT 0 NOT NULL,
    auto_assigned boolean DEFAULT false NOT NULL,
    assignment_criteria json,
    automation_rules json,
    auto_upgrade_tier boolean DEFAULT false NOT NULL,
    sla_entitlements json,
    priority_level character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    escalation_rules json,
    notification_preferences json,
    can_collaborate_with_team boolean DEFAULT true NOT NULL,
    collaboration_settings json,
    receives_service_updates boolean DEFAULT true NOT NULL,
    receives_maintenance_notifications boolean DEFAULT true NOT NULL,
    security_requirements json,
    compliance_settings json,
    data_access_restrictions json,
    requires_mfa boolean DEFAULT false NOT NULL,
    usage_history json,
    billing_history json,
    lifetime_value numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    average_monthly_usage numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    assignment_notes text,
    metadata json,
    custom_fields json,
    assigned_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contract_contact_assignments_access_level_check CHECK (((access_level)::text = ANY (ARRAY[('basic'::character varying)::text, ('standard'::character varying)::text, ('premium'::character varying)::text, ('admin'::character varying)::text, ('custom'::character varying)::text]))),
    CONSTRAINT contract_contact_assignments_billing_frequency_check CHECK (((billing_frequency)::text = ANY (ARRAY[('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('annually'::character varying)::text, ('per_ticket'::character varying)::text]))),
    CONSTRAINT contract_contact_assignments_priority_level_check CHECK (((priority_level)::text = ANY (ARRAY[('low'::character varying)::text, ('normal'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text]))),
    CONSTRAINT contract_contact_assignments_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('suspended'::character varying)::text, ('terminated'::character varying)::text, ('pending'::character varying)::text])))
);


--
-- Name: contract_contact_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_contact_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_contact_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_contact_assignments_id_seq OWNED BY public.contract_contact_assignments.id;


--
-- Name: contract_dashboard_widgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_dashboard_widgets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    widget_slug character varying(255) NOT NULL,
    widget_type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    config json,
    data_source_config json,
    display_config json,
    filter_config json,
    contract_types_filter json,
    position_x integer DEFAULT 0 NOT NULL,
    position_y integer DEFAULT 0 NOT NULL,
    width integer DEFAULT 1 NOT NULL,
    height integer DEFAULT 1 NOT NULL,
    permissions json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_dashboard_widgets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_dashboard_widgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_dashboard_widgets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_dashboard_widgets_id_seq OWNED BY public.contract_dashboard_widgets.id;


--
-- Name: contract_detail_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_detail_configurations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_type_slug character varying(255) NOT NULL,
    sections_config json,
    tabs_config json,
    sidebar_config json,
    actions_config json,
    related_data_config json,
    timeline_config json,
    show_timeline boolean DEFAULT true NOT NULL,
    show_related_records boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_detail_configurations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_detail_configurations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_detail_configurations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_detail_configurations_id_seq OWNED BY public.contract_detail_configurations.id;


--
-- Name: contract_field_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_field_definitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    field_slug character varying(255) NOT NULL,
    field_type character varying(255) NOT NULL,
    label character varying(255) NOT NULL,
    placeholder character varying(255),
    help_text text,
    validation_rules json,
    ui_config json,
    options json,
    is_required boolean DEFAULT false NOT NULL,
    is_searchable boolean DEFAULT false NOT NULL,
    is_sortable boolean DEFAULT false NOT NULL,
    is_filterable boolean DEFAULT false NOT NULL,
    default_value character varying(255),
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_field_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_field_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_field_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_field_definitions_id_seq OWNED BY public.contract_field_definitions.id;


--
-- Name: contract_form_sections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_form_sections (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    section_slug character varying(255) NOT NULL,
    section_name character varying(255) NOT NULL,
    description text,
    icon character varying(255),
    fields_order json,
    conditional_logic json,
    layout_config json,
    is_collapsible boolean DEFAULT false NOT NULL,
    is_collapsed_by_default boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_form_sections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_form_sections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_form_sections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_form_sections_id_seq OWNED BY public.contract_form_sections.id;


--
-- Name: contract_invoice; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_invoice (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    invoice_type character varying(255),
    invoiced_amount numeric(10,2),
    description text,
    milestone_id bigint,
    billing_period_start date,
    billing_period_end date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_invoice_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_invoice_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_invoice_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_invoice_id_seq OWNED BY public.contract_invoice.id;


--
-- Name: contract_list_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_list_configurations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_type_slug character varying(255) NOT NULL,
    columns_config json,
    filters_config json,
    search_config json,
    sorting_config json,
    pagination_config json,
    bulk_actions_config json,
    export_config json,
    show_row_actions boolean DEFAULT true NOT NULL,
    show_bulk_actions boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_list_configurations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_list_configurations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_list_configurations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_list_configurations_id_seq OWNED BY public.contract_list_configurations.id;


--
-- Name: contract_menu_sections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_menu_sections (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    section_slug character varying(255) NOT NULL,
    section_name character varying(255) NOT NULL,
    icon character varying(255),
    sort_order integer DEFAULT 0 NOT NULL,
    contract_types json,
    permissions json,
    config json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_menu_sections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_menu_sections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_menu_sections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_menu_sections_id_seq OWNED BY public.contract_menu_sections.id;


--
-- Name: contract_milestones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_milestones (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    due_date date NOT NULL,
    completed_date date,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    is_billable boolean DEFAULT true NOT NULL,
    is_invoiced boolean DEFAULT false NOT NULL,
    invoice_id bigint,
    deliverables json,
    requires_approval boolean DEFAULT false NOT NULL,
    approved_at timestamp(0) without time zone,
    approved_by bigint,
    approval_notes text,
    progress_percentage integer DEFAULT 0 NOT NULL,
    progress_notes text,
    depends_on_milestone_id bigint,
    sort_order integer DEFAULT 0 NOT NULL,
    send_reminder boolean DEFAULT true NOT NULL,
    reminder_days_before integer DEFAULT 7 NOT NULL,
    reminder_sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: contract_milestones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_milestones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_milestones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_milestones_id_seq OWNED BY public.contract_milestones.id;


--
-- Name: contract_navigation_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_navigation_items (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    label character varying(255) NOT NULL,
    icon character varying(255),
    route character varying(255),
    parent_slug character varying(255),
    sort_order integer DEFAULT 0 NOT NULL,
    permissions json,
    conditions json,
    config json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_navigation_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_navigation_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_navigation_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_navigation_items_id_seq OWNED BY public.contract_navigation_items.id;


--
-- Name: contract_negotiations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_negotiations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    client_id bigint NOT NULL,
    quote_id bigint,
    negotiation_number character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    phase character varying(255) DEFAULT 'preparation'::character varying NOT NULL,
    round integer DEFAULT 1 NOT NULL,
    started_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    deadline timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    last_activity_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    internal_participants json NOT NULL,
    client_participants json,
    permissions json,
    objectives json,
    constraints json,
    competitive_context json,
    current_version_id bigint,
    pricing_history json,
    target_value numeric(12,2),
    minimum_value numeric(12,2),
    final_value numeric(12,2),
    duration_days integer,
    won boolean,
    outcome_notes text,
    created_by bigint NOT NULL,
    assigned_to bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_negotiations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_negotiations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_negotiations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_negotiations_id_seq OWNED BY public.contract_negotiations.id;


--
-- Name: contract_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_schedules (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    schedule_type character varying(255) NOT NULL,
    schedule_letter character varying(1) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    content text NOT NULL,
    variables json,
    variable_values json,
    required_fields json,
    supported_asset_types json,
    service_levels json,
    coverage_rules json,
    sla_terms json,
    response_times json,
    coverage_hours json,
    escalation_procedures json,
    pricing_structure json,
    billing_rules json,
    rate_tables json,
    discount_structures json,
    penalty_structures json,
    asset_inclusion_rules json,
    asset_exclusion_rules json,
    location_coverage json,
    client_tier_requirements json,
    auto_assign_assets boolean DEFAULT false NOT NULL,
    require_manual_approval boolean DEFAULT true NOT NULL,
    automation_rules json,
    assignment_triggers json,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    approval_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approval_notes text,
    approved_at timestamp(0) without time zone,
    approved_by bigint,
    version character varying(20) DEFAULT '1.0'::character varying NOT NULL,
    parent_schedule_id bigint,
    template_id bigint,
    is_template boolean DEFAULT false NOT NULL,
    asset_count integer DEFAULT 0 NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    last_used_at timestamp(0) without time zone,
    effectiveness_score numeric(5,2),
    effective_date date,
    expiration_date date,
    last_reviewed_at timestamp(0) without time zone,
    next_review_date date,
    created_by bigint,
    updated_by bigint,
    metadata json,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    CONSTRAINT contract_schedules_approval_status_check CHECK (((approval_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text, ('changes_requested'::character varying)::text]))),
    CONSTRAINT contract_schedules_schedule_type_check CHECK (((schedule_type)::text = ANY (ARRAY[('A'::character varying)::text, ('B'::character varying)::text, ('C'::character varying)::text, ('D'::character varying)::text, ('E'::character varying)::text]))),
    CONSTRAINT contract_schedules_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('pending_approval'::character varying)::text, ('active'::character varying)::text, ('suspended'::character varying)::text, ('archived'::character varying)::text])))
);


--
-- Name: COLUMN contract_schedules.schedule_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contract_schedules.schedule_type IS 'A=Infrastructure/SLA, B=Pricing, C=Additional Terms, etc.';


--
-- Name: contract_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_schedules_id_seq OWNED BY public.contract_schedules.id;


--
-- Name: contract_signatures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_signatures (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_id bigint NOT NULL,
    signer_type character varying(255) NOT NULL,
    signer_role character varying(255),
    signer_name character varying(255) NOT NULL,
    signer_email character varying(255) NOT NULL,
    signer_title character varying(255),
    signer_company character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    signed_at timestamp(0) without time zone,
    signature_method character varying(255),
    signature_data text,
    signature_hash character varying(255),
    ip_address character varying(45),
    user_agent text,
    verification_code character varying(255),
    verification_sent_at timestamp(0) without time zone,
    verified_at timestamp(0) without time zone,
    document_version character varying(255),
    document_hash character varying(255),
    consent_to_electronic_signature boolean DEFAULT false NOT NULL,
    consent_given_at timestamp(0) without time zone,
    additional_terms_accepted text,
    invitation_sent_at timestamp(0) without time zone,
    last_reminder_sent_at timestamp(0) without time zone,
    reminder_count integer DEFAULT 0 NOT NULL,
    expires_at timestamp(0) without time zone,
    decline_reason text,
    declined_at timestamp(0) without time zone,
    audit_trail json,
    signing_order integer DEFAULT 1 NOT NULL,
    requires_previous_signatures boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_signatures_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_signatures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_signatures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_signatures_id_seq OWNED BY public.contract_signatures.id;


--
-- Name: contract_status_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_status_definitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    color character varying(255) DEFAULT '#6b7280'::character varying NOT NULL,
    icon character varying(255),
    is_initial boolean DEFAULT false NOT NULL,
    is_final boolean DEFAULT false NOT NULL,
    config json,
    permissions json,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_status_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_status_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_status_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_status_definitions_id_seq OWNED BY public.contract_status_definitions.id;


--
-- Name: contract_status_transitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_status_transitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    from_status_slug character varying(255) NOT NULL,
    to_status_slug character varying(255) NOT NULL,
    label character varying(255),
    description text,
    conditions json,
    required_permissions json,
    required_fields json,
    actions json,
    notifications json,
    requires_confirmation boolean DEFAULT false NOT NULL,
    confirmation_message character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_status_transitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_status_transitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_status_transitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_status_transitions_id_seq OWNED BY public.contract_status_transitions.id;


--
-- Name: contract_template_clauses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_template_clauses (
    id bigint NOT NULL,
    template_id bigint NOT NULL,
    clause_id bigint NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_required boolean,
    conditions json,
    variable_overrides json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_template_clauses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_template_clauses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_template_clauses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_template_clauses_id_seq OWNED BY public.contract_template_clauses.id;


--
-- Name: contract_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    template_type character varying(255) NOT NULL,
    category character varying(255),
    tags json,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    version character varying(20) DEFAULT '1.0'::character varying NOT NULL,
    parent_template_id bigint,
    is_default boolean DEFAULT false NOT NULL,
    template_content text NOT NULL,
    variable_fields json,
    default_values json,
    required_fields json,
    billing_model character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    pricing_structure json,
    asset_billing_rules json,
    supported_asset_types json,
    asset_service_matrix json,
    default_per_asset_rate numeric(10,2),
    contact_billing_rules json,
    contact_access_tiers json,
    default_per_contact_rate numeric(10,2),
    voip_service_types json,
    default_sla_terms json,
    default_pricing_structure json,
    compliance_templates json,
    jurisdictions json,
    regulatory_requirements json,
    legal_disclaimers text,
    calculation_formulas json,
    auto_assignment_rules json,
    billing_triggers json,
    workflow_automation json,
    notification_triggers json,
    integration_hooks json,
    customization_options json,
    conditional_clauses json,
    pricing_models json,
    usage_count integer DEFAULT 0 NOT NULL,
    last_used_at timestamp(0) without time zone,
    success_rate numeric(5,2),
    requires_approval boolean DEFAULT false NOT NULL,
    approval_workflow json,
    last_reviewed_at timestamp(0) without time zone,
    next_review_date date,
    metadata json,
    rendering_options json,
    signature_settings json,
    created_by bigint,
    updated_by bigint,
    approved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    automation_settings json,
    archived_at timestamp(0) without time zone,
    CONSTRAINT contract_templates_billing_model_check CHECK (((billing_model)::text = ANY (ARRAY[('fixed'::character varying)::text, ('per_asset'::character varying)::text, ('per_contact'::character varying)::text, ('tiered'::character varying)::text, ('hybrid'::character varying)::text]))),
    CONSTRAINT contract_templates_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('active'::character varying)::text, ('archived'::character varying)::text])))
);


--
-- Name: contract_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_templates_id_seq OWNED BY public.contract_templates.id;


--
-- Name: contract_type_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_type_definitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    icon character varying(255),
    color character varying(255),
    config json,
    default_values json,
    business_rules json,
    permissions json,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_type_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_type_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_type_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_type_definitions_id_seq OWNED BY public.contract_type_definitions.id;


--
-- Name: contract_type_form_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_type_form_mappings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_type_slug character varying(255) NOT NULL,
    section_slug character varying(255) NOT NULL,
    is_required boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    conditional_logic json,
    field_overrides json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_type_form_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_type_form_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_type_form_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_type_form_mappings_id_seq OWNED BY public.contract_type_form_mappings.id;


--
-- Name: contract_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_versions (
    id bigint NOT NULL,
    contract_id bigint NOT NULL,
    version_number character varying(255) NOT NULL,
    version_type character varying(255) DEFAULT 'revision'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    change_summary text,
    changes json,
    contract_data json NOT NULL,
    components json NOT NULL,
    pricing_snapshot json NOT NULL,
    approval_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approvals json,
    rejection_reason text,
    negotiation_id bigint,
    branch character varying(255),
    is_client_visible boolean DEFAULT false NOT NULL,
    is_final boolean DEFAULT false NOT NULL,
    created_by bigint NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_versions_id_seq OWNED BY public.contract_versions.id;


--
-- Name: contract_view_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contract_view_definitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_type_slug character varying(255) NOT NULL,
    view_type character varying(255) NOT NULL,
    layout_config json,
    fields_config json,
    actions_config json,
    permissions json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contract_view_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contract_view_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contract_view_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contract_view_definitions_id_seq OWNED BY public.contract_view_definitions.id;


--
-- Name: contracts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contracts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    contract_number character varying(255) NOT NULL,
    contract_type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    client_id bigint NOT NULL,
    contract_value numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    currency_code character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    payment_terms character varying(255),
    discount_percentage numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    tax_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    start_date date NOT NULL,
    end_date date,
    term_months integer,
    signed_date date,
    terms_and_conditions text,
    scope_of_work text,
    deliverables json,
    metadata json,
    auto_renew boolean DEFAULT false NOT NULL,
    renewal_notice_days integer,
    renewal_date date,
    renewal_type character varying(255),
    quote_id bigint,
    project_id bigint,
    created_by bigint,
    approved_by bigint,
    document_path character varying(255),
    template_used character varying(255),
    requires_approval boolean DEFAULT false NOT NULL,
    approved_at timestamp(0) without time zone,
    approval_notes text,
    sla_terms json,
    performance_metrics json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    billing_model character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    pricing_structure json,
    asset_billing_rules json,
    supported_asset_types json,
    default_per_asset_rate numeric(10,2),
    contact_billing_rules json,
    contact_access_tiers json,
    signature_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    is_programmable boolean DEFAULT false NOT NULL,
    contract_template_id bigint,
    default_per_contact_rate numeric(10,2),
    calculation_formulas json,
    auto_assignment_rules json,
    billing_triggers json,
    workflow_automation json,
    notification_triggers json,
    total_assigned_assets integer DEFAULT 0 NOT NULL,
    total_assigned_contacts integer DEFAULT 0 NOT NULL,
    monthly_usage_charges numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    asset_billing_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    contact_billing_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    last_billing_calculation timestamp(0) without time zone,
    next_billing_date date,
    auto_calculate_billing boolean DEFAULT true NOT NULL,
    auto_generate_invoices boolean DEFAULT false NOT NULL,
    automation_settings json,
    requires_manual_review boolean DEFAULT false NOT NULL,
    calculation_cache json,
    cache_expires_at timestamp(0) without time zone,
    template_version character varying(20),
    template_id bigint,
    custom_clauses json,
    dispute_resolution character varying(255),
    governing_law character varying(255),
    content text,
    variables json,
    archived_at timestamp(0) without time zone,
    CONSTRAINT contracts_billing_model_check CHECK (((billing_model)::text = ANY (ARRAY[('fixed'::character varying)::text, ('per_asset'::character varying)::text, ('per_contact'::character varying)::text, ('tiered'::character varying)::text, ('hybrid'::character varying)::text])))
);


--
-- Name: contracts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contracts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contracts_id_seq OWNED BY public.contracts.id;


--
-- Name: conversion_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversion_events (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    lead_id bigint,
    contact_id bigint,
    client_id bigint,
    event_type character varying(255) NOT NULL,
    value numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    attributed_campaign_id bigint,
    attribution_model character varying(255) DEFAULT 'last_touch'::character varying NOT NULL,
    attribution_data json,
    metadata json,
    converted_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: conversion_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.conversion_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: conversion_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.conversion_events_id_seq OWNED BY public.conversion_events.id;


--
-- Name: credit_applications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_applications (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    applied_by bigint,
    application_date date,
    deleted_at timestamp(0) without time zone,
    application_number character varying(255) NOT NULL
);


--
-- Name: credit_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_applications_id_seq OWNED BY public.credit_applications.id;


--
-- Name: credit_note_approvals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_note_approvals (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    requested_at timestamp(0) without time zone,
    reviewed_at timestamp(0) without time zone,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    expired_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    credit_note_id bigint NOT NULL,
    approver_id bigint,
    requested_by bigint,
    approval_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    sla_deadline timestamp(0) without time zone
);


--
-- Name: credit_note_approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_note_approvals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_note_approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_note_approvals_id_seq OWNED BY public.credit_note_approvals.id;


--
-- Name: credit_note_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_note_items (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    description character varying(255),
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    credit_note_id bigint NOT NULL,
    name character varying(255),
    item_type character varying(255) DEFAULT 'product'::character varying NOT NULL,
    quantity numeric(10,2) DEFAULT '1'::numeric NOT NULL,
    unit_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    line_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    remaining_credit numeric(10,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: credit_note_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_note_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_note_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_note_items_id_seq OWNED BY public.credit_note_items.id;


--
-- Name: credit_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_notes (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255),
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    credit_date date,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    deleted_at timestamp(0) without time zone,
    client_id bigint,
    created_by bigint,
    number character varying(255),
    type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    remaining_balance numeric(10,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: credit_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_notes_id_seq OWNED BY public.credit_notes.id;


--
-- Name: cross_company_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cross_company_users (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    company_id bigint NOT NULL,
    primary_company_id bigint NOT NULL,
    role_in_company integer NOT NULL,
    access_type character varying(255) DEFAULT 'limited'::character varying NOT NULL,
    access_permissions json,
    access_restrictions json,
    authorized_by bigint,
    delegated_from bigint,
    authorization_reason text,
    is_active boolean DEFAULT true NOT NULL,
    access_granted_at timestamp(0) without time zone,
    access_expires_at timestamp(0) without time zone,
    last_accessed_at timestamp(0) without time zone,
    require_re_auth boolean DEFAULT false NOT NULL,
    max_concurrent_sessions integer DEFAULT 1 NOT NULL,
    allowed_features json,
    audit_actions boolean DEFAULT true NOT NULL,
    compliance_settings json,
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cross_company_users_access_type_check CHECK (((access_type)::text = ANY (ARRAY[('full'::character varying)::text, ('limited'::character varying)::text, ('view_only'::character varying)::text])))
);


--
-- Name: cross_company_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cross_company_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cross_company_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cross_company_users_id_seq OWNED BY public.cross_company_users.id;


--
-- Name: custom_quick_actions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_quick_actions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint,
    title character varying(50) NOT NULL,
    description character varying(255) NOT NULL,
    icon character varying(50) DEFAULT 'bolt'::character varying NOT NULL,
    color character varying(255) DEFAULT 'blue'::character varying NOT NULL,
    type character varying(255) DEFAULT 'route'::character varying NOT NULL,
    target character varying(255) NOT NULL,
    parameters json,
    open_in character varying(255) DEFAULT 'same_tab'::character varying NOT NULL,
    visibility character varying(255) DEFAULT 'private'::character varying NOT NULL,
    allowed_roles json,
    permission character varying(255),
    "position" integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT custom_quick_actions_color_check CHECK (((color)::text = ANY (ARRAY[('blue'::character varying)::text, ('green'::character varying)::text, ('purple'::character varying)::text, ('orange'::character varying)::text, ('red'::character varying)::text, ('yellow'::character varying)::text, ('gray'::character varying)::text]))),
    CONSTRAINT custom_quick_actions_open_in_check CHECK (((open_in)::text = ANY (ARRAY[('same_tab'::character varying)::text, ('new_tab'::character varying)::text]))),
    CONSTRAINT custom_quick_actions_type_check CHECK (((type)::text = ANY (ARRAY[('route'::character varying)::text, ('url'::character varying)::text]))),
    CONSTRAINT custom_quick_actions_visibility_check CHECK (((visibility)::text = ANY (ARRAY[('private'::character varying)::text, ('role'::character varying)::text, ('company'::character varying)::text])))
);


--
-- Name: COLUMN custom_quick_actions.target; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.custom_quick_actions.target IS 'Route name or URL';


--
-- Name: COLUMN custom_quick_actions.parameters; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.custom_quick_actions.parameters IS 'Route parameters or URL query params';


--
-- Name: COLUMN custom_quick_actions.allowed_roles; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.custom_quick_actions.allowed_roles IS 'Roles that can see this action when visibility is role';


--
-- Name: COLUMN custom_quick_actions.permission; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.custom_quick_actions.permission IS 'Required permission to use this action';


--
-- Name: custom_quick_actions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.custom_quick_actions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: custom_quick_actions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.custom_quick_actions_id_seq OWNED BY public.custom_quick_actions.id;


--
-- Name: dashboard_activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dashboard_activity_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    company_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    details json,
    ip_address character varying(255),
    user_agent character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: dashboard_activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dashboard_activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dashboard_activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dashboard_activity_logs_id_seq OWNED BY public.dashboard_activity_logs.id;


--
-- Name: dashboard_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dashboard_metrics (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    metric_key character varying(255) NOT NULL,
    value numeric(20,4) NOT NULL,
    previous_value numeric(20,4),
    change_percentage numeric(8,2),
    trend character varying(255),
    breakdown json,
    calculated_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: dashboard_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dashboard_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dashboard_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dashboard_metrics_id_seq OWNED BY public.dashboard_metrics.id;


--
-- Name: dashboard_presets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dashboard_presets (
    id bigint NOT NULL,
    company_id bigint,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    role character varying(255),
    layout json NOT NULL,
    widgets json NOT NULL,
    default_preferences json NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: dashboard_presets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dashboard_presets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dashboard_presets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dashboard_presets_id_seq OWNED BY public.dashboard_presets.id;


--
-- Name: dashboard_widgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dashboard_widgets (
    id bigint NOT NULL,
    widget_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    category character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    description text,
    default_config json NOT NULL,
    available_sizes json NOT NULL,
    data_source character varying(255) NOT NULL,
    min_refresh_interval integer DEFAULT 30 NOT NULL,
    required_permissions json,
    is_active boolean DEFAULT true NOT NULL,
    icon character varying(255),
    color_scheme character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: dashboard_widgets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dashboard_widgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dashboard_widgets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dashboard_widgets_id_seq OWNED BY public.dashboard_widgets.id;


--
-- Name: device_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.device_mappings (
    id bigint NOT NULL,
    uuid uuid NOT NULL,
    integration_id bigint NOT NULL,
    rmm_device_id character varying(255) NOT NULL,
    asset_id bigint,
    client_id bigint NOT NULL,
    device_name character varying(255) NOT NULL,
    sync_data json,
    last_updated timestamp(0) without time zone NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: device_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.device_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: device_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.device_mappings_id_seq OWNED BY public.device_mappings.id;


--
-- Name: documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documents (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    documentable_type character varying(255) NOT NULL,
    documentable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    file_path character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    file_size bigint,
    mime_type character varying(255),
    category character varying(255) DEFAULT 'other'::character varying NOT NULL,
    is_private boolean DEFAULT false NOT NULL,
    uploaded_by bigint,
    tags json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documents_id_seq OWNED BY public.documents.id;


--
-- Name: dunning_actions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dunning_actions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    campaign_id bigint,
    sequence_id bigint,
    client_id bigint NOT NULL,
    invoice_id bigint,
    action_reference character varying(255) NOT NULL,
    action_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    scheduled_at timestamp(0) without time zone,
    attempted_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    retry_count integer DEFAULT 0 NOT NULL,
    next_retry_at timestamp(0) without time zone,
    recipient_email character varying(255),
    recipient_phone character varying(255),
    recipient_name character varying(255),
    message_subject character varying(255),
    message_content text,
    template_used character varying(255),
    email_message_id character varying(255),
    sms_message_id character varying(255),
    call_session_id character varying(255),
    delivery_metadata json,
    opened boolean DEFAULT false NOT NULL,
    opened_at timestamp(0) without time zone,
    clicked boolean DEFAULT false NOT NULL,
    clicked_at timestamp(0) without time zone,
    response_type character varying(255),
    responded_at timestamp(0) without time zone,
    response_data json,
    invoice_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    amount_due numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    late_fees numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    days_overdue integer DEFAULT 0 NOT NULL,
    settlement_offer_amount numeric(15,2),
    amount_collected numeric(15,2),
    suspended_services json,
    maintained_services json,
    suspension_effective_at timestamp(0) without time zone,
    restoration_scheduled_at timestamp(0) without time zone,
    suspension_reason character varying(255),
    final_notice boolean DEFAULT false NOT NULL,
    legal_action_threatened boolean DEFAULT false NOT NULL,
    compliance_flags json,
    legal_disclaimer text,
    dispute_period_active boolean DEFAULT false NOT NULL,
    dispute_deadline timestamp(0) without time zone,
    escalated boolean DEFAULT false NOT NULL,
    escalated_to_user_id bigint,
    escalated_at timestamp(0) without time zone,
    escalation_reason character varying(255),
    escalation_level integer,
    cost_per_action numeric(10,4),
    resulted_in_payment boolean DEFAULT false NOT NULL,
    roi numeric(10,4),
    client_satisfaction_score integer,
    error_message text,
    error_details text,
    last_error_at timestamp(0) without time zone,
    requires_manual_review boolean DEFAULT false NOT NULL,
    pause_sequence boolean DEFAULT false NOT NULL,
    pause_reason character varying(255),
    sequence_resumed_at timestamp(0) without time zone,
    next_action_id bigint,
    created_by bigint,
    processed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT dunning_actions_action_type_check CHECK (((action_type)::text = ANY (ARRAY[('email'::character varying)::text, ('sms'::character varying)::text, ('phone_call'::character varying)::text, ('letter'::character varying)::text, ('service_suspension'::character varying)::text, ('legal_notice'::character varying)::text]))),
    CONSTRAINT dunning_actions_response_type_check CHECK (((response_type)::text = ANY (ARRAY[('payment'::character varying)::text, ('dispute'::character varying)::text, ('promise_to_pay'::character varying)::text, ('no_response'::character varying)::text]))),
    CONSTRAINT dunning_actions_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('scheduled'::character varying)::text, ('processing'::character varying)::text, ('sent'::character varying)::text, ('delivered'::character varying)::text, ('failed'::character varying)::text, ('bounced'::character varying)::text, ('opened'::character varying)::text, ('clicked'::character varying)::text, ('responded'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text, ('escalated'::character varying)::text])))
);


--
-- Name: dunning_actions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dunning_actions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dunning_actions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dunning_actions_id_seq OWNED BY public.dunning_actions.id;


--
-- Name: dunning_campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dunning_campaigns (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    campaign_type character varying(255) DEFAULT 'automatic'::character varying NOT NULL,
    created_by bigint
);


--
-- Name: dunning_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dunning_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dunning_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dunning_campaigns_id_seq OWNED BY public.dunning_campaigns.id;


--
-- Name: dunning_sequences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dunning_sequences (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    campaign_id bigint,
    step_number integer DEFAULT 1 NOT NULL,
    action_type character varying(255) DEFAULT 'email'::character varying NOT NULL,
    created_by bigint,
    updated_by bigint
);


--
-- Name: dunning_sequences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dunning_sequences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dunning_sequences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dunning_sequences_id_seq OWNED BY public.dunning_sequences.id;


--
-- Name: email_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_accounts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email_address character varying(255) NOT NULL,
    provider character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    imap_host character varying(255) NOT NULL,
    imap_port integer DEFAULT 993 NOT NULL,
    imap_encryption character varying(255) DEFAULT 'ssl'::character varying NOT NULL,
    imap_username character varying(255) NOT NULL,
    imap_password text NOT NULL,
    imap_validate_cert boolean DEFAULT true NOT NULL,
    smtp_host character varying(255) NOT NULL,
    smtp_port integer DEFAULT 587 NOT NULL,
    smtp_encryption character varying(255) DEFAULT 'tls'::character varying NOT NULL,
    smtp_username character varying(255) NOT NULL,
    smtp_password text NOT NULL,
    oauth_access_token text,
    oauth_refresh_token text,
    oauth_expires_at timestamp(0) without time zone,
    oauth_scopes json,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sync_interval_minutes integer DEFAULT 5 NOT NULL,
    last_synced_at timestamp(0) without time zone,
    sync_error text,
    auto_create_tickets boolean DEFAULT false NOT NULL,
    auto_log_communications boolean DEFAULT true NOT NULL,
    filters json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    connection_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    oauth_provider character varying(255),
    oauth_token_expires_at timestamp(0) without time zone,
    CONSTRAINT email_accounts_connection_type_check CHECK (((connection_type)::text = ANY (ARRAY[('manual'::character varying)::text, ('oauth'::character varying)::text])))
);


--
-- Name: email_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_accounts_id_seq OWNED BY public.email_accounts.id;


--
-- Name: email_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_attachments (
    id bigint NOT NULL,
    email_message_id bigint NOT NULL,
    filename character varying(255) NOT NULL,
    content_type character varying(255) NOT NULL,
    size_bytes integer NOT NULL,
    content_id character varying(255),
    is_inline boolean DEFAULT false NOT NULL,
    encoding character varying(255),
    disposition character varying(255) DEFAULT 'attachment'::character varying NOT NULL,
    storage_disk character varying(255) DEFAULT 'local'::character varying NOT NULL,
    storage_path character varying(255) NOT NULL,
    hash character varying(255),
    is_image boolean DEFAULT false NOT NULL,
    thumbnail_path character varying(255),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: email_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_attachments_id_seq OWNED BY public.email_attachments.id;


--
-- Name: email_folders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_folders (
    id bigint NOT NULL,
    email_account_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    path character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'custom'::character varying NOT NULL,
    message_count integer DEFAULT 0 NOT NULL,
    unread_count integer DEFAULT 0 NOT NULL,
    is_subscribed boolean DEFAULT true NOT NULL,
    is_selectable boolean DEFAULT true NOT NULL,
    attributes json,
    last_synced_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    remote_id character varying(255)
);


--
-- Name: email_folders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_folders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_folders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_folders_id_seq OWNED BY public.email_folders.id;


--
-- Name: email_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_messages (
    id bigint NOT NULL,
    email_account_id bigint NOT NULL,
    email_folder_id bigint NOT NULL,
    message_id character varying(255) NOT NULL,
    uid character varying(255) NOT NULL,
    thread_id character varying(255),
    reply_to_message_id bigint,
    subject character varying(255),
    from_address text NOT NULL,
    from_name character varying(255),
    to_addresses text NOT NULL,
    cc_addresses text,
    bcc_addresses text,
    reply_to_addresses text,
    body_text text,
    body_html text,
    preview text,
    sent_at timestamp(0) without time zone,
    received_at timestamp(0) without time zone,
    size_bytes integer DEFAULT 0 NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    is_flagged boolean DEFAULT false NOT NULL,
    is_draft boolean DEFAULT false NOT NULL,
    is_answered boolean DEFAULT false NOT NULL,
    is_deleted boolean DEFAULT false NOT NULL,
    has_attachments boolean DEFAULT false NOT NULL,
    is_ticket_created boolean DEFAULT false NOT NULL,
    ticket_id bigint,
    is_communication_logged boolean DEFAULT false NOT NULL,
    communication_log_id bigint,
    headers json,
    flags json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    remote_id character varying(255)
);


--
-- Name: email_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_messages_id_seq OWNED BY public.email_messages.id;


--
-- Name: email_signatures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_signatures (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    email_account_id bigint,
    name character varying(255) NOT NULL,
    content_html text,
    content_text text,
    is_default boolean DEFAULT false NOT NULL,
    auto_append_replies boolean DEFAULT true NOT NULL,
    auto_append_forwards boolean DEFAULT true NOT NULL,
    conditions json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: email_signatures_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_signatures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_signatures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_signatures_id_seq OWNED BY public.email_signatures.id;


--
-- Name: email_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    body_html text NOT NULL,
    body_text text,
    category character varying(255) DEFAULT 'marketing'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: email_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_templates_id_seq OWNED BY public.email_templates.id;


--
-- Name: email_tracking; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_tracking (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    tracking_id character varying(255) NOT NULL,
    lead_id bigint,
    contact_id bigint,
    recipient_email character varying(255) NOT NULL,
    campaign_id bigint,
    campaign_sequence_id bigint,
    email_type character varying(255) DEFAULT 'campaign'::character varying NOT NULL,
    subject_line character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'sent'::character varying NOT NULL,
    sent_at timestamp(0) without time zone NOT NULL,
    delivered_at timestamp(0) without time zone,
    bounced_at timestamp(0) without time zone,
    bounce_reason text,
    first_opened_at timestamp(0) without time zone,
    last_opened_at timestamp(0) without time zone,
    open_count integer DEFAULT 0 NOT NULL,
    first_clicked_at timestamp(0) without time zone,
    last_clicked_at timestamp(0) without time zone,
    click_count integer DEFAULT 0 NOT NULL,
    replied_at timestamp(0) without time zone,
    unsubscribed_at timestamp(0) without time zone,
    user_agent text,
    ip_address character varying(255),
    location character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_tracking_status_check CHECK (((status)::text = ANY (ARRAY[('sent'::character varying)::text, ('delivered'::character varying)::text, ('bounced'::character varying)::text, ('failed'::character varying)::text])))
);


--
-- Name: email_tracking_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_tracking_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_tracking_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_tracking_id_seq OWNED BY public.email_tracking.id;


--
-- Name: employee_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employee_schedules (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    shift_id bigint NOT NULL,
    scheduled_date date NOT NULL,
    start_time time(0) without time zone NOT NULL,
    end_time time(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT employee_schedules_status_check CHECK (((status)::text = ANY (ARRAY[('scheduled'::character varying)::text, ('confirmed'::character varying)::text, ('missed'::character varying)::text, ('completed'::character varying)::text])))
);


--
-- Name: employee_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.employee_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: employee_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.employee_schedules_id_seq OWNED BY public.employee_schedules.id;


--
-- Name: employee_time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employee_time_entries (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    shift_id bigint,
    pay_period_id bigint,
    clock_in timestamp(0) without time zone NOT NULL,
    clock_out timestamp(0) without time zone,
    total_minutes integer,
    regular_minutes integer,
    overtime_minutes integer,
    double_time_minutes integer DEFAULT 0,
    break_minutes integer DEFAULT 0 NOT NULL,
    entry_type character varying(255) DEFAULT 'clock'::character varying NOT NULL,
    status character varying(255) DEFAULT 'in_progress'::character varying NOT NULL,
    clock_in_ip character varying(255),
    clock_out_ip character varying(255),
    clock_in_latitude numeric(10,7),
    clock_in_longitude numeric(10,7),
    clock_out_latitude numeric(10,7),
    clock_out_longitude numeric(10,7),
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    rejected_by bigint,
    rejected_at timestamp(0) without time zone,
    notes text,
    rejection_reason text,
    exported_to_payroll boolean DEFAULT false NOT NULL,
    exported_at timestamp(0) without time zone,
    payroll_batch_id character varying(255),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT employee_time_entries_entry_type_check CHECK (((entry_type)::text = ANY (ARRAY[('clock'::character varying)::text, ('manual'::character varying)::text, ('imported'::character varying)::text, ('adjusted'::character varying)::text]))),
    CONSTRAINT employee_time_entries_status_check CHECK (((status)::text = ANY (ARRAY[('in_progress'::character varying)::text, ('completed'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text, ('paid'::character varying)::text])))
);


--
-- Name: employee_time_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.employee_time_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: employee_time_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.employee_time_entries_id_seq OWNED BY public.employee_time_entries.id;


--
-- Name: expenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.expenses (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    category_id bigint NOT NULL,
    user_id bigint NOT NULL,
    description character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    expense_date date NOT NULL,
    receipt_path character varying(255),
    notes text,
    is_billable boolean DEFAULT false NOT NULL,
    client_id bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT expenses_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text])))
);


--
-- Name: expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expenses_id_seq OWNED BY public.expenses.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.files (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    fileable_type character varying(255) NOT NULL,
    fileable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    file_path character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    original_name character varying(255) NOT NULL,
    file_size bigint,
    mime_type character varying(255),
    file_type character varying(255) DEFAULT 'other'::character varying NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    uploaded_by bigint,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.files_id_seq OWNED BY public.files.id;


--
-- Name: financial_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.financial_reports (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: financial_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.financial_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: financial_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.financial_reports_id_seq OWNED BY public.financial_reports.id;


--
-- Name: hr_settings_overrides; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_settings_overrides (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    overridable_type character varying(255) NOT NULL,
    overridable_id bigint NOT NULL,
    setting_key character varying(255) NOT NULL,
    setting_value json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hr_settings_overrides_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_settings_overrides_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_settings_overrides_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_settings_overrides_id_seq OWNED BY public.hr_settings_overrides.id;


--
-- Name: in_app_notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.in_app_notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    link character varying(255),
    icon character varying(255),
    color character varying(255) DEFAULT 'blue'::character varying NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    ticket_id bigint,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: in_app_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.in_app_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: in_app_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.in_app_notifications_id_seq OWNED BY public.in_app_notifications.id;


--
-- Name: invoice_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_items (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    quantity numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    price numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tax_breakdown json,
    service_data json,
    tax_rate numeric(8,4),
    service_type character varying(50),
    tax_jurisdiction_id bigint,
    archived_at timestamp(0) without time zone,
    tax_id bigint,
    quote_id bigint,
    recurring_id bigint,
    invoice_id bigint,
    category_id bigint,
    product_id bigint
);


--
-- Name: invoice_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoice_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoice_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoice_items_id_seq OWNED BY public.invoice_items.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    prefix character varying(255),
    number integer NOT NULL,
    scope character varying(255),
    status character varying(255) NOT NULL,
    date date NOT NULL,
    due_date date NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency_code character varying(3) NOT NULL,
    note text,
    url_key character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    contract_id bigint,
    is_recurring boolean DEFAULT false NOT NULL,
    recurring_invoice_id bigint,
    recurring_frequency character varying(255),
    next_recurring_date date,
    archived_at timestamp(0) without time zone,
    category_id bigint NOT NULL,
    client_id bigint NOT NULL,
    project_id bigint
);


--
-- Name: COLUMN invoices.recurring_frequency; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.invoices.recurring_frequency IS 'monthly, quarterly, yearly, etc.';


--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: ip_lookup_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ip_lookup_logs (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    ip_address character varying(45) NOT NULL,
    country character varying(100),
    country_code character varying(2),
    region character varying(100),
    region_code character varying(10),
    city character varying(100),
    zip character varying(20),
    latitude numeric(10,6),
    longitude numeric(10,6),
    timezone character varying(50),
    isp character varying(255),
    is_valid boolean DEFAULT true NOT NULL,
    is_vpn boolean DEFAULT false NOT NULL,
    is_proxy boolean DEFAULT false NOT NULL,
    is_tor boolean DEFAULT false NOT NULL,
    threat_level character varying(255) DEFAULT 'low'::character varying NOT NULL,
    lookup_source character varying(255) DEFAULT 'api_ninjas'::character varying NOT NULL,
    api_response json,
    cached_until timestamp(0) without time zone,
    lookup_count integer DEFAULT 1 NOT NULL,
    last_lookup_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT ip_lookup_logs_lookup_source_check CHECK (((lookup_source)::text = ANY (ARRAY[('api_ninjas'::character varying)::text, ('ipapi'::character varying)::text, ('maxmind'::character varying)::text]))),
    CONSTRAINT ip_lookup_logs_threat_level_check CHECK (((threat_level)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('critical'::character varying)::text])))
);


--
-- Name: ip_lookup_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ip_lookup_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ip_lookup_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ip_lookup_logs_id_seq OWNED BY public.ip_lookup_logs.id;


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: kpi_calculations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kpi_calculations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: kpi_calculations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kpi_calculations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kpi_calculations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kpi_calculations_id_seq OWNED BY public.kpi_calculations.id;


--
-- Name: kpi_targets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kpi_targets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    kpi_name character varying(255) NOT NULL,
    kpi_type character varying(255) NOT NULL,
    target_value numeric(15,4) NOT NULL,
    comparison_operator character varying(255) DEFAULT '>='::character varying NOT NULL,
    period character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    start_date date NOT NULL,
    end_date date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT kpi_targets_comparison_operator_check CHECK (((comparison_operator)::text = ANY (ARRAY[('>'::character varying)::text, ('<'::character varying)::text, ('>='::character varying)::text, ('<='::character varying)::text, ('='::character varying)::text]))),
    CONSTRAINT kpi_targets_period_check CHECK (((period)::text = ANY (ARRAY[('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('yearly'::character varying)::text])))
);


--
-- Name: kpi_targets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kpi_targets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kpi_targets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kpi_targets_id_seq OWNED BY public.kpi_targets.id;


--
-- Name: lead_activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lead_activities (
    id bigint NOT NULL,
    lead_id bigint NOT NULL,
    user_id bigint,
    type character varying(255) NOT NULL,
    subject character varying(255),
    description text,
    metadata json,
    activity_date timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lead_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lead_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lead_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lead_activities_id_seq OWNED BY public.lead_activities.id;


--
-- Name: lead_sources; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lead_sources (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    description text,
    settings json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: lead_sources_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lead_sources_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lead_sources_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lead_sources_id_seq OWNED BY public.lead_sources.id;


--
-- Name: leads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.leads (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    lead_source_id bigint,
    assigned_user_id bigint,
    client_id bigint,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    phone character varying(255),
    company_name character varying(255),
    title character varying(255),
    website character varying(255),
    address text,
    city character varying(255),
    state character varying(255),
    zip_code character varying(255),
    country character varying(255),
    status character varying(255) DEFAULT 'new'::character varying NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    industry character varying(255),
    company_size integer,
    estimated_value numeric(10,2),
    notes text,
    custom_fields json,
    total_score integer DEFAULT 0 NOT NULL,
    demographic_score integer DEFAULT 0 NOT NULL,
    behavioral_score integer DEFAULT 0 NOT NULL,
    fit_score integer DEFAULT 0 NOT NULL,
    urgency_score integer DEFAULT 0 NOT NULL,
    last_scored_at timestamp(0) without time zone,
    first_contact_date timestamp(0) without time zone,
    last_contact_date timestamp(0) without time zone,
    qualified_at timestamp(0) without time zone,
    converted_at timestamp(0) without time zone,
    utm_source character varying(255),
    utm_medium character varying(255),
    utm_campaign character varying(255),
    utm_content character varying(255),
    utm_term character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT leads_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text]))),
    CONSTRAINT leads_status_check CHECK (((status)::text = ANY (ARRAY[('new'::character varying)::text, ('contacted'::character varying)::text, ('qualified'::character varying)::text, ('unqualified'::character varying)::text, ('nurturing'::character varying)::text, ('converted'::character varying)::text, ('lost'::character varying)::text])))
);


--
-- Name: leads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.leads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: leads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.leads_id_seq OWNED BY public.leads.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.locations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    country character varying(255),
    address character varying(255),
    city character varying(255),
    state character varying(255),
    zip character varying(255),
    phone character varying(255),
    hours character varying(255),
    photo character varying(255),
    "primary" boolean DEFAULT false NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    client_id bigint NOT NULL,
    contact_id bigint
);


--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: mail_queue; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mail_queue (
    id bigint NOT NULL,
    uuid uuid NOT NULL,
    company_id bigint,
    client_id bigint,
    contact_id bigint,
    user_id bigint,
    from_email character varying(255),
    from_name character varying(255),
    to_email character varying(255) NOT NULL,
    to_name character varying(255),
    cc json,
    bcc json,
    reply_to character varying(255),
    subject character varying(255) NOT NULL,
    html_body text,
    text_body text,
    attachments json,
    headers json,
    template character varying(255),
    template_data json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    max_attempts integer DEFAULT 3 NOT NULL,
    scheduled_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    failed_at timestamp(0) without time zone,
    next_retry_at timestamp(0) without time zone,
    last_error text,
    error_log json,
    failure_reason character varying(255),
    mailer character varying(255) DEFAULT 'smtp'::character varying NOT NULL,
    message_id character varying(255),
    provider_response json,
    tracking_token character varying(255),
    opened_at timestamp(0) without time zone,
    open_count integer DEFAULT 0 NOT NULL,
    opens json,
    click_count integer DEFAULT 0 NOT NULL,
    clicks json,
    category character varying(255),
    related_type character varying(255),
    related_id bigint,
    tags json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT mail_queue_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('normal'::character varying)::text, ('high'::character varying)::text, ('critical'::character varying)::text]))),
    CONSTRAINT mail_queue_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('processing'::character varying)::text, ('sent'::character varying)::text, ('failed'::character varying)::text, ('bounced'::character varying)::text, ('complained'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: COLUMN mail_queue.user_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.user_id IS 'User who initiated the email';


--
-- Name: COLUMN mail_queue.template; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.template IS 'Template name if using template';


--
-- Name: COLUMN mail_queue.template_data; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.template_data IS 'Data passed to template';


--
-- Name: COLUMN mail_queue.scheduled_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.scheduled_at IS 'When to send the email';


--
-- Name: COLUMN mail_queue.error_log; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.error_log IS 'History of all errors';


--
-- Name: COLUMN mail_queue.failure_reason; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.failure_reason IS 'Categorized failure reason';


--
-- Name: COLUMN mail_queue.mailer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.mailer IS 'Mail driver used (smtp, ses, mailgun, etc)';


--
-- Name: COLUMN mail_queue.message_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.message_id IS 'Provider message ID';


--
-- Name: COLUMN mail_queue.opens; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.opens IS 'History of all opens';


--
-- Name: COLUMN mail_queue.clicks; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.clicks IS 'History of all clicks';


--
-- Name: COLUMN mail_queue.category; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.category IS 'Email category (invoice, notification, marketing, etc)';


--
-- Name: COLUMN mail_queue.related_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.related_type IS 'Related model type';


--
-- Name: COLUMN mail_queue.related_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_queue.related_id IS 'Related model ID';


--
-- Name: mail_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mail_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mail_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mail_queue_id_seq OWNED BY public.mail_queue.id;


--
-- Name: mail_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mail_templates (
    id bigint NOT NULL,
    company_id bigint,
    name character varying(255) NOT NULL,
    display_name character varying(255) NOT NULL,
    category character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    html_template text NOT NULL,
    text_template text,
    available_variables json,
    default_data json,
    is_active boolean DEFAULT true NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN mail_templates.category; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_templates.category IS 'invoice, notification, marketing, system, etc';


--
-- Name: COLUMN mail_templates.available_variables; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_templates.available_variables IS 'List of variables that can be used in template';


--
-- Name: COLUMN mail_templates.default_data; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_templates.default_data IS 'Default values for variables';


--
-- Name: COLUMN mail_templates.is_system; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.mail_templates.is_system IS 'System templates cannot be deleted';


--
-- Name: mail_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mail_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mail_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mail_templates_id_seq OWNED BY public.mail_templates.id;


--
-- Name: marketing_campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.marketing_campaigns (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    created_by_user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    type character varying(255) DEFAULT 'email'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    settings json,
    target_criteria json,
    auto_enroll boolean DEFAULT false NOT NULL,
    start_date timestamp(0) without time zone,
    end_date timestamp(0) without time zone,
    total_recipients integer DEFAULT 0 NOT NULL,
    total_sent integer DEFAULT 0 NOT NULL,
    total_delivered integer DEFAULT 0 NOT NULL,
    total_opened integer DEFAULT 0 NOT NULL,
    total_clicked integer DEFAULT 0 NOT NULL,
    total_replied integer DEFAULT 0 NOT NULL,
    total_unsubscribed integer DEFAULT 0 NOT NULL,
    total_converted integer DEFAULT 0 NOT NULL,
    total_revenue numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT marketing_campaigns_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('scheduled'::character varying)::text, ('active'::character varying)::text, ('paused'::character varying)::text, ('completed'::character varying)::text, ('archived'::character varying)::text]))),
    CONSTRAINT marketing_campaigns_type_check CHECK (((type)::text = ANY (ARRAY[('email'::character varying)::text, ('nurture'::character varying)::text, ('drip'::character varying)::text, ('event'::character varying)::text, ('webinar'::character varying)::text, ('content'::character varying)::text])))
);


--
-- Name: marketing_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.marketing_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: marketing_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.marketing_campaigns_id_seq OWNED BY public.marketing_campaigns.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media (
    id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid uuid,
    collection_name character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(255),
    disk character varying(255) NOT NULL,
    conversions_disk character varying(255),
    size bigint NOT NULL,
    manipulations json NOT NULL,
    custom_properties json NOT NULL,
    generated_conversions json NOT NULL,
    responsive_images json NOT NULL,
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: networks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.networks (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    vlan integer,
    network character varying(255) NOT NULL,
    gateway character varying(255) NOT NULL,
    dhcp_range character varying(255),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    location_id bigint,
    client_id bigint NOT NULL
);


--
-- Name: networks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.networks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: networks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.networks_id_seq OWNED BY public.networks.id;


--
-- Name: notification_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notification_logs (
    id bigint NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint,
    notification_type character varying(255) NOT NULL,
    channels_sent json NOT NULL,
    channels_failed json NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


--
-- Name: notification_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notification_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notification_logs_id_seq OWNED BY public.notification_logs.id;


--
-- Name: notification_preferences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notification_preferences (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    ticket_created boolean DEFAULT true NOT NULL,
    ticket_assigned boolean DEFAULT true NOT NULL,
    ticket_status_changed boolean DEFAULT true NOT NULL,
    ticket_resolved boolean DEFAULT true NOT NULL,
    ticket_comment_added boolean DEFAULT true NOT NULL,
    sla_breach_warning boolean DEFAULT true NOT NULL,
    sla_breached boolean DEFAULT true NOT NULL,
    daily_digest boolean DEFAULT false NOT NULL,
    email_enabled boolean DEFAULT true NOT NULL,
    in_app_enabled boolean DEFAULT true NOT NULL,
    push_enabled boolean DEFAULT true NOT NULL,
    digest_time time(0) without time zone DEFAULT '08:00:00'::time without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    name character varying(255) DEFAULT 'Default'::character varying NOT NULL,
    company_id bigint
);


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_preferences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notification_preferences_id_seq OWNED BY public.notification_preferences.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: oauth_states; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_states (
    id bigint NOT NULL,
    state character varying(64) NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    provider character varying(255) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: oauth_states_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oauth_states_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oauth_states_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oauth_states_id_seq OWNED BY public.oauth_states.id;


--
-- Name: pay_periods; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pay_periods (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    frequency character varying(255) NOT NULL,
    approved_at timestamp(0) without time zone,
    approved_by bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT pay_periods_frequency_check CHECK (((frequency)::text = ANY (ARRAY[('weekly'::character varying)::text, ('biweekly'::character varying)::text, ('semimonthly'::character varying)::text, ('monthly'::character varying)::text]))),
    CONSTRAINT pay_periods_status_check CHECK (((status)::text = ANY (ARRAY[('open'::character varying)::text, ('in_review'::character varying)::text, ('approved'::character varying)::text, ('paid'::character varying)::text])))
);


--
-- Name: pay_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pay_periods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pay_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pay_periods_id_seq OWNED BY public.pay_periods.id;


--
-- Name: payment_applications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_applications (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    payment_id bigint NOT NULL,
    applicable_type character varying(255) NOT NULL,
    applicable_id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    applied_date date NOT NULL,
    applied_by bigint,
    is_active boolean DEFAULT true NOT NULL,
    unapplied_at timestamp(0) without time zone,
    unapplied_by bigint,
    unapplication_reason text,
    notes text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: payment_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_applications_id_seq OWNED BY public.payment_applications.id;


--
-- Name: payment_methods; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_methods (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    type character varying(50) NOT NULL,
    provider character varying(50) NOT NULL,
    provider_payment_method_id character varying(255),
    provider_customer_id character varying(255),
    token character varying(255),
    fingerprint character varying(255),
    name character varying(255),
    description character varying(255),
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    verified boolean DEFAULT false NOT NULL,
    verified_at timestamp(0) without time zone,
    card_brand character varying(20),
    card_last_four character varying(4),
    card_exp_month character varying(2),
    card_exp_year character varying(4),
    card_holder_name character varying(255),
    card_country character varying(2),
    card_funding character varying(20),
    card_checks_cvc_check boolean,
    card_checks_address_line1_check boolean,
    card_checks_address_postal_code_check boolean,
    bank_name character varying(255),
    bank_account_type character varying(20),
    bank_account_last_four character varying(4),
    bank_routing_number_last_four character varying(4),
    bank_account_holder_type character varying(20),
    bank_account_holder_name character varying(255),
    bank_country character varying(2),
    bank_currency character varying(3),
    wallet_type character varying(50),
    wallet_email character varying(255),
    wallet_phone character varying(255),
    crypto_type character varying(20),
    crypto_address character varying(255),
    crypto_network character varying(255),
    billing_name character varying(255),
    billing_email character varying(255),
    billing_phone character varying(255),
    billing_address_line1 character varying(255),
    billing_address_line2 character varying(255),
    billing_city character varying(255),
    billing_state character varying(255),
    billing_postal_code character varying(255),
    billing_country character varying(2),
    security_checks json,
    compliance_data json,
    requires_3d_secure boolean DEFAULT false NOT NULL,
    risk_assessment json,
    successful_payments_count integer DEFAULT 0 NOT NULL,
    failed_payments_count integer DEFAULT 0 NOT NULL,
    total_payment_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    last_used_at timestamp(0) without time zone,
    last_failed_at timestamp(0) without time zone,
    last_failure_reason character varying(255),
    metadata json,
    preferences json,
    restrictions json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: payment_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_methods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_methods_id_seq OWNED BY public.payment_methods.id;


--
-- Name: payment_plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_plans (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255),
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    plan_number character varying(255) NOT NULL,
    client_id bigint,
    plan_type character varying(255) DEFAULT 'custom'::character varying NOT NULL,
    original_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    plan_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_by bigint
);


--
-- Name: payment_plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_plans_id_seq OWNED BY public.payment_plans.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    processed_by bigint,
    payment_method character varying(50) NOT NULL,
    payment_reference character varying(255),
    amount numeric(10,2) NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    gateway character varying(50) DEFAULT 'manual'::character varying NOT NULL,
    gateway_transaction_id character varying(255),
    gateway_fee numeric(8,2),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_date timestamp(0) without time zone NOT NULL,
    notes text,
    metadata json,
    refund_amount numeric(10,2),
    refund_reason text,
    refunded_at timestamp(0) without time zone,
    chargeback_amount numeric(10,2),
    chargeback_reason text,
    chargeback_date timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    applied_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    available_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    application_status character varying(255) DEFAULT 'unapplied'::character varying NOT NULL,
    auto_apply boolean DEFAULT true NOT NULL,
    CONSTRAINT payments_application_status_check CHECK (((application_status)::text = ANY (ARRAY[('unapplied'::character varying)::text, ('partially_applied'::character varying)::text, ('fully_applied'::character varying)::text]))),
    CONSTRAINT payments_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('processing'::character varying)::text, ('completed'::character varying)::text, ('failed'::character varying)::text, ('cancelled'::character varying)::text, ('refunded'::character varying)::text, ('partial_refund'::character varying)::text, ('chargeback'::character varying)::text])))
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: permission_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permission_groups (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permission_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permission_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permission_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permission_groups_id_seq OWNED BY public.permission_groups.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: physical_mail_bank_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_bank_accounts (
    id uuid NOT NULL,
    postgrid_id character varying(255),
    company_id bigint NOT NULL,
    account_name character varying(255) NOT NULL,
    account_number text NOT NULL,
    routing_number text NOT NULL,
    bank_name character varying(255),
    signature_image text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: physical_mail_cheques; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_cheques (
    id uuid NOT NULL,
    to_contact_id uuid NOT NULL,
    from_contact_id uuid NOT NULL,
    bank_account_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    memo character varying(255),
    message_content text,
    check_number character varying(255),
    digital_only boolean DEFAULT false NOT NULL,
    size character varying(255) DEFAULT 'us_letter'::character varying NOT NULL,
    idempotency_key character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_cheques_size_check CHECK (((size)::text = ANY (ARRAY[('us_letter'::character varying)::text, ('us_legal'::character varying)::text])))
);


--
-- Name: physical_mail_contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_contacts (
    id uuid NOT NULL,
    postgrid_id character varying(255),
    client_id bigint,
    first_name character varying(255),
    last_name character varying(255),
    company_name character varying(255),
    job_title character varying(255),
    address_line1 character varying(255) NOT NULL,
    address_line2 character varying(255),
    city character varying(255),
    province_or_state character varying(255),
    postal_or_zip character varying(255),
    country_code character varying(2) DEFAULT 'US'::character varying NOT NULL,
    email character varying(255),
    phone_number character varying(255),
    address_status character varying(255) DEFAULT 'unverified'::character varying NOT NULL,
    address_change json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_contacts_address_status_check CHECK (((address_status)::text = ANY (ARRAY[('verified'::character varying)::text, ('corrected'::character varying)::text, ('unverified'::character varying)::text])))
);


--
-- Name: physical_mail_letters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_letters (
    id uuid NOT NULL,
    to_contact_id uuid NOT NULL,
    from_contact_id uuid NOT NULL,
    template_id uuid,
    content text,
    color boolean DEFAULT false NOT NULL,
    double_sided boolean DEFAULT false NOT NULL,
    address_placement character varying(255) DEFAULT 'top_first_page'::character varying NOT NULL,
    size character varying(255) DEFAULT 'us_letter'::character varying NOT NULL,
    perforated_page integer,
    return_envelope_id uuid,
    extra_service character varying(255),
    merge_variables json,
    idempotency_key character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_letters_address_placement_check CHECK (((address_placement)::text = ANY (ARRAY[('top_first_page'::character varying)::text, ('insert_blank_page'::character varying)::text]))),
    CONSTRAINT physical_mail_letters_extra_service_check CHECK (((extra_service)::text = ANY (ARRAY[('certified'::character varying)::text, ('certified_return_receipt'::character varying)::text, ('registered'::character varying)::text]))),
    CONSTRAINT physical_mail_letters_size_check CHECK (((size)::text = ANY (ARRAY[('us_letter'::character varying)::text, ('us_legal'::character varying)::text, ('a4'::character varying)::text])))
);


--
-- Name: physical_mail_orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_orders (
    id uuid NOT NULL,
    client_id bigint,
    mailable_type character varying(255) NOT NULL,
    mailable_id uuid NOT NULL,
    postgrid_id character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    imb_status character varying(255),
    imb_date timestamp(0) without time zone,
    imb_zip_code character varying(255),
    tracking_number character varying(255),
    mailing_class character varying(255) DEFAULT 'first_class'::character varying NOT NULL,
    send_date timestamp(0) without time zone,
    cost numeric(8,2),
    pdf_url text,
    metadata json,
    created_by bigint,
    company_id bigint,
    latitude numeric(10,8),
    longitude numeric(11,8),
    formatted_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_orders_imb_status_check CHECK (((imb_status)::text = ANY (ARRAY[('entered_mail_stream'::character varying)::text, ('out_for_delivery'::character varying)::text, ('returned_to_sender'::character varying)::text]))),
    CONSTRAINT physical_mail_orders_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('ready'::character varying)::text, ('printing'::character varying)::text, ('processed_for_delivery'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text, ('failed'::character varying)::text])))
);


--
-- Name: physical_mail_postcards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_postcards (
    id uuid NOT NULL,
    to_contact_id uuid NOT NULL,
    from_contact_id uuid NOT NULL,
    template_id uuid,
    front_content text,
    back_content text,
    size character varying(255) DEFAULT '6x4'::character varying NOT NULL,
    merge_variables json,
    idempotency_key character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_postcards_size_check CHECK (((size)::text = ANY (ARRAY[('6x4'::character varying)::text, ('9x6'::character varying)::text, ('11x6'::character varying)::text])))
);


--
-- Name: physical_mail_return_envelopes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_return_envelopes (
    id uuid NOT NULL,
    postgrid_id character varying(255),
    contact_id uuid NOT NULL,
    quantity_ordered integer DEFAULT 0 NOT NULL,
    quantity_available integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: physical_mail_self_mailers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_self_mailers (
    id uuid NOT NULL,
    to_contact_id uuid NOT NULL,
    from_contact_id uuid NOT NULL,
    template_id uuid,
    content text,
    size character varying(255) DEFAULT '8.5x11_bifold'::character varying NOT NULL,
    merge_variables json,
    idempotency_key character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_self_mailers_size_check CHECK (((size)::text = ANY (ARRAY[('8.5x11_bifold'::character varying)::text, ('8.5x11_trifold'::character varying)::text])))
);


--
-- Name: physical_mail_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_settings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    test_key character varying(255),
    live_key character varying(255),
    webhook_secret character varying(255),
    force_test_mode boolean DEFAULT false NOT NULL,
    from_company_name character varying(255),
    from_contact_name character varying(255),
    from_address_line1 character varying(255),
    from_address_line2 character varying(255),
    from_city character varying(255),
    from_state character varying(2),
    from_zip character varying(10),
    from_country character varying(2) DEFAULT 'US'::character varying NOT NULL,
    default_mailing_class character varying(255) DEFAULT 'first_class'::character varying NOT NULL,
    default_color_printing boolean DEFAULT true NOT NULL,
    default_double_sided boolean DEFAULT false NOT NULL,
    default_address_placement character varying(255) DEFAULT 'top_first_page'::character varying NOT NULL,
    default_size character varying(255) DEFAULT 'us_letter'::character varying NOT NULL,
    track_costs boolean DEFAULT true NOT NULL,
    markup_percentage numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    include_tax boolean DEFAULT false NOT NULL,
    enable_ncoa boolean DEFAULT true NOT NULL,
    enable_address_verification boolean DEFAULT true NOT NULL,
    enable_return_envelopes boolean DEFAULT false NOT NULL,
    enable_bulk_mail boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_connection_test timestamp(0) without time zone,
    last_connection_status character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: physical_mail_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.physical_mail_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: physical_mail_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.physical_mail_settings_id_seq OWNED BY public.physical_mail_settings.id;


--
-- Name: physical_mail_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_templates (
    id uuid NOT NULL,
    postgrid_id character varying(255),
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    content text,
    description text,
    variables json,
    is_active boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT physical_mail_templates_type_check CHECK (((type)::text = ANY (ARRAY[('letter'::character varying)::text, ('postcard'::character varying)::text, ('cheque'::character varying)::text, ('self_mailer'::character varying)::text])))
);


--
-- Name: physical_mail_webhooks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.physical_mail_webhooks (
    id uuid NOT NULL,
    postgrid_event_id character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    payload json NOT NULL,
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: portal_notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.portal_notifications (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    category character varying(255),
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    description text,
    data json,
    icon character varying(255),
    color character varying(255),
    action_url character varying(255),
    action_text character varying(255),
    show_in_portal boolean DEFAULT true NOT NULL,
    send_email boolean DEFAULT false NOT NULL,
    send_sms boolean DEFAULT false NOT NULL,
    send_push boolean DEFAULT false NOT NULL,
    delivery_channels json,
    email_subject character varying(255),
    email_body text,
    email_template character varying(255),
    email_sent_at timestamp(0) without time zone,
    email_delivered boolean,
    email_error character varying(255),
    sms_message character varying(255),
    sms_sent_at timestamp(0) without time zone,
    sms_delivered boolean,
    sms_error character varying(255),
    push_title character varying(255),
    push_body text,
    push_data json,
    push_sent_at timestamp(0) without time zone,
    push_delivered boolean,
    push_error character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    is_dismissed boolean DEFAULT false NOT NULL,
    dismissed_at timestamp(0) without time zone,
    requires_action boolean DEFAULT false NOT NULL,
    action_completed boolean DEFAULT false NOT NULL,
    action_completed_at timestamp(0) without time zone,
    scheduled_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    is_recurring boolean DEFAULT false NOT NULL,
    recurring_pattern character varying(255),
    next_occurrence timestamp(0) without time zone,
    target_conditions json,
    personalization_data json,
    language character varying(10) DEFAULT 'en'::character varying NOT NULL,
    timezone character varying(255),
    invoice_id bigint,
    payment_id bigint,
    ticket_id bigint,
    contract_id bigint,
    related_model_type character varying(255),
    related_model_id bigint,
    group_key character varying(255),
    parent_id bigint,
    thread_position integer,
    is_summary boolean DEFAULT false NOT NULL,
    tracking_data json,
    view_count integer DEFAULT 0 NOT NULL,
    first_viewed_at timestamp(0) without time zone,
    last_viewed_at timestamp(0) without time zone,
    click_count integer DEFAULT 0 NOT NULL,
    first_clicked_at timestamp(0) without time zone,
    last_clicked_at timestamp(0) without time zone,
    variant character varying(255),
    campaign_id character varying(255),
    experiment_data json,
    requires_acknowledgment boolean DEFAULT false NOT NULL,
    acknowledged_at timestamp(0) without time zone,
    acknowledgment_method character varying(255),
    audit_trail json,
    respects_do_not_disturb boolean DEFAULT true NOT NULL,
    client_preferences json,
    can_be_disabled boolean DEFAULT true NOT NULL,
    frequency_limit character varying(255),
    source_system character varying(255),
    external_id character varying(255),
    webhook_data json,
    trigger_webhooks boolean DEFAULT false NOT NULL,
    metadata json,
    custom_fields json,
    internal_notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT portal_notifications_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('normal'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text, ('critical'::character varying)::text]))),
    CONSTRAINT portal_notifications_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('sent'::character varying)::text, ('delivered'::character varying)::text, ('read'::character varying)::text, ('failed'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: portal_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.portal_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: portal_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.portal_notifications_id_seq OWNED BY public.portal_notifications.id;


--
-- Name: pricing_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pricing_rules (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    product_id bigint NOT NULL,
    client_id bigint,
    name character varying(255),
    pricing_model character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    discount_type character varying(255),
    discount_value numeric(10,2),
    price_override numeric(10,2),
    min_quantity integer,
    max_quantity integer,
    quantity_increment integer DEFAULT 1 NOT NULL,
    valid_from timestamp(0) without time zone,
    valid_until timestamp(0) without time zone,
    applicable_days json,
    applicable_hours json,
    is_promotional boolean DEFAULT false NOT NULL,
    promo_code character varying(255),
    conditions json,
    priority integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_combinable boolean DEFAULT false NOT NULL,
    max_uses integer,
    uses_count integer DEFAULT 0 NOT NULL,
    max_uses_per_client integer,
    requires_approval boolean DEFAULT false NOT NULL,
    approval_threshold numeric(10,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT pricing_rules_discount_type_check CHECK (((discount_type)::text = ANY (ARRAY[('percentage'::character varying)::text, ('fixed'::character varying)::text, ('override'::character varying)::text]))),
    CONSTRAINT pricing_rules_pricing_model_check CHECK (((pricing_model)::text = ANY (ARRAY[('fixed'::character varying)::text, ('tiered'::character varying)::text, ('volume'::character varying)::text, ('usage'::character varying)::text, ('package'::character varying)::text, ('custom'::character varying)::text])))
);


--
-- Name: pricing_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pricing_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pricing_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pricing_rules_id_seq OWNED BY public.pricing_rules.id;


--
-- Name: product_bundle_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_bundle_items (
    id bigint NOT NULL,
    bundle_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    is_required boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT true NOT NULL,
    discount_type character varying(255) DEFAULT 'none'::character varying NOT NULL,
    discount_value numeric(10,2),
    price_override numeric(10,2),
    min_quantity integer DEFAULT 0 NOT NULL,
    max_quantity integer,
    allowed_variants json,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT product_bundle_items_discount_type_check CHECK (((discount_type)::text = ANY (ARRAY[('percentage'::character varying)::text, ('fixed'::character varying)::text, ('none'::character varying)::text])))
);


--
-- Name: product_bundle_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_bundle_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_bundle_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_bundle_items_id_seq OWNED BY public.product_bundle_items.id;


--
-- Name: product_bundles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_bundles (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    sku character varying(255),
    bundle_type character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    pricing_type character varying(255) DEFAULT 'sum'::character varying NOT NULL,
    fixed_price numeric(10,2),
    discount_percentage numeric(5,2),
    min_value numeric(10,2),
    is_active boolean DEFAULT true NOT NULL,
    available_from timestamp(0) without time zone,
    available_until timestamp(0) without time zone,
    max_quantity integer,
    image_url character varying(255),
    show_items_separately boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT product_bundles_bundle_type_check CHECK (((bundle_type)::text = ANY (ARRAY[('fixed'::character varying)::text, ('configurable'::character varying)::text, ('dynamic'::character varying)::text]))),
    CONSTRAINT product_bundles_pricing_type_check CHECK (((pricing_type)::text = ANY (ARRAY[('sum'::character varying)::text, ('fixed'::character varying)::text, ('percentage_discount'::character varying)::text])))
);


--
-- Name: product_bundles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_bundles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_bundles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_bundles_id_seq OWNED BY public.product_bundles.id;


--
-- Name: product_tax_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_tax_data (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    product_id bigint NOT NULL,
    tax_profile_id bigint,
    tax_data json NOT NULL,
    calculated_taxes json,
    jurisdiction_id bigint,
    effective_tax_rate numeric(8,6),
    total_tax_amount numeric(15,2),
    last_calculated_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_tax_data_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_tax_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_tax_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_tax_data_id_seq OWNED BY public.product_tax_data.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    sku character varying(255),
    description text,
    short_description text,
    type character varying(255) DEFAULT 'product'::character varying NOT NULL,
    base_price numeric(15,2) NOT NULL,
    cost numeric(10,2),
    currency_code character varying(3) NOT NULL,
    category_id bigint NOT NULL,
    subcategory_id bigint,
    tax_inclusive boolean DEFAULT false NOT NULL,
    tax_rate numeric(5,2),
    tax_id bigint,
    tax_profile_id bigint,
    unit_type character varying(255) DEFAULT 'units'::character varying NOT NULL,
    billing_model character varying(255) DEFAULT 'one_time'::character varying NOT NULL,
    billing_cycle character varying(255) DEFAULT 'one_time'::character varying NOT NULL,
    billing_interval integer DEFAULT 1 NOT NULL,
    track_inventory boolean DEFAULT false NOT NULL,
    current_stock integer DEFAULT 0 NOT NULL,
    reserved_stock integer DEFAULT 0 NOT NULL,
    min_stock_level integer DEFAULT 0 NOT NULL,
    max_quantity_per_order integer,
    reorder_level integer,
    is_active boolean DEFAULT true NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    is_taxable boolean DEFAULT true NOT NULL,
    allow_discounts boolean DEFAULT true NOT NULL,
    requires_approval boolean DEFAULT false NOT NULL,
    pricing_model character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    pricing_tiers json,
    discount_percentage numeric(5,2),
    usage_rate numeric(10,4),
    usage_included integer,
    features json,
    tags json,
    metadata json,
    custom_fields json,
    image_url character varying(255),
    gallery_urls json,
    sales_count integer DEFAULT 0 NOT NULL,
    total_revenue numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    average_rating numeric(3,2),
    rating_count integer DEFAULT 0 NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    vendor_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT products_billing_cycle_check CHECK (((billing_cycle)::text = ANY (ARRAY[('one_time'::character varying)::text, ('hourly'::character varying)::text, ('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('semi_annually'::character varying)::text, ('annually'::character varying)::text]))),
    CONSTRAINT products_billing_model_check CHECK (((billing_model)::text = ANY (ARRAY[('one_time'::character varying)::text, ('subscription'::character varying)::text, ('usage_based'::character varying)::text, ('hybrid'::character varying)::text]))),
    CONSTRAINT products_pricing_model_check CHECK (((pricing_model)::text = ANY (ARRAY[('fixed'::character varying)::text, ('tiered'::character varying)::text, ('volume'::character varying)::text, ('usage'::character varying)::text, ('value'::character varying)::text, ('custom'::character varying)::text]))),
    CONSTRAINT products_type_check CHECK (((type)::text = ANY (ARRAY[('product'::character varying)::text, ('service'::character varying)::text]))),
    CONSTRAINT products_unit_type_check CHECK (((unit_type)::text = ANY (ARRAY[('hours'::character varying)::text, ('units'::character varying)::text, ('days'::character varying)::text, ('weeks'::character varying)::text, ('months'::character varying)::text, ('years'::character varying)::text, ('fixed'::character varying)::text, ('subscription'::character varying)::text])))
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: project_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_comments (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    comment text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: project_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_comments_id_seq OWNED BY public.project_comments.id;


--
-- Name: project_expenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_expenses (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    category character varying(255),
    description text NOT NULL,
    amount numeric(10,2) NOT NULL,
    date date NOT NULL,
    receipt_url character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT project_expenses_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text, ('reimbursed'::character varying)::text])))
);


--
-- Name: project_expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_expenses_id_seq OWNED BY public.project_expenses.id;


--
-- Name: project_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_files (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    path character varying(255) NOT NULL,
    size bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: project_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_files_id_seq OWNED BY public.project_files.id;


--
-- Name: project_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_members (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(255) DEFAULT 'member'::character varying NOT NULL,
    hourly_rate numeric(10,2),
    can_log_time boolean DEFAULT true NOT NULL,
    can_edit_tasks boolean DEFAULT false NOT NULL,
    joined_at timestamp(0) without time zone NOT NULL,
    left_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT project_members_role_check CHECK (((role)::text = ANY (ARRAY[('manager'::character varying)::text, ('member'::character varying)::text, ('viewer'::character varying)::text])))
);


--
-- Name: project_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_members_id_seq OWNED BY public.project_members.id;


--
-- Name: project_milestones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_milestones (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    project_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    due_date date NOT NULL,
    completed_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    deliverables json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT project_milestones_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('overdue'::character varying)::text])))
);


--
-- Name: project_milestones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_milestones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_milestones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_milestones_id_seq OWNED BY public.project_milestones.id;


--
-- Name: project_tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_tasks (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    project_id bigint NOT NULL,
    milestone_id bigint,
    parent_task_id bigint,
    name character varying(255) NOT NULL,
    description text,
    assigned_to bigint,
    start_date timestamp(0) without time zone,
    due_date timestamp(0) without time zone,
    completed_date timestamp(0) without time zone,
    estimated_hours integer,
    actual_hours integer,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'not_started'::character varying NOT NULL,
    completion_percentage integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT project_tasks_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text]))),
    CONSTRAINT project_tasks_status_check CHECK (((status)::text = ANY (ARRAY[('not_started'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('on_hold'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: project_tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_tasks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_tasks_id_seq OWNED BY public.project_tasks.id;


--
-- Name: project_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    default_milestones json,
    default_tasks json,
    estimated_duration_days integer,
    is_active boolean DEFAULT true NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: project_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_templates_id_seq OWNED BY public.project_templates.id;


--
-- Name: project_time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_time_entries (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    task_id bigint,
    description text,
    hours numeric(8,2) NOT NULL,
    date date NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    billed boolean DEFAULT false NOT NULL,
    rate numeric(10,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: project_time_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_time_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_time_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_time_entries_id_seq OWNED BY public.project_time_entries.id;


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    prefix character varying(255),
    number integer DEFAULT 1 NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    due date,
    manager_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    progress integer DEFAULT 0 NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    start_date date,
    budget numeric(10,2),
    actual_cost numeric(10,2),
    completed_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    client_id bigint NOT NULL
);


--
-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;


--
-- Name: push_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_subscriptions (
    id bigint NOT NULL,
    subscribable_type character varying(255) NOT NULL,
    subscribable_id bigint NOT NULL,
    endpoint character varying(500) NOT NULL,
    public_key character varying(255),
    auth_token character varying(255),
    content_encoding character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.push_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.push_subscriptions_id_seq OWNED BY public.push_subscriptions.id;


--
-- Name: quick_action_favorites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quick_action_favorites (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    custom_quick_action_id bigint,
    system_action character varying(255),
    "position" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN quick_action_favorites.system_action; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.quick_action_favorites.system_action IS 'For favoriting system-defined actions';


--
-- Name: quick_action_favorites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quick_action_favorites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quick_action_favorites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quick_action_favorites_id_seq OWNED BY public.quick_action_favorites.id;


--
-- Name: quote_approvals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quote_approvals (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    quote_id bigint NOT NULL,
    user_id bigint NOT NULL,
    approval_level character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    comments text,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    CONSTRAINT quote_approvals_approval_level_check CHECK (((approval_level)::text = ANY (ARRAY[('manager'::character varying)::text, ('executive'::character varying)::text, ('finance'::character varying)::text]))),
    CONSTRAINT quote_approvals_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text])))
);


--
-- Name: quote_approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quote_approvals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quote_approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quote_approvals_id_seq OWNED BY public.quote_approvals.id;


--
-- Name: quote_invoice_conversions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quote_invoice_conversions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    quote_id bigint,
    invoice_id bigint,
    conversion_type character varying(255) DEFAULT 'full'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    activation_status character varying(255) DEFAULT 'not_required'::character varying NOT NULL,
    current_step integer DEFAULT 1 NOT NULL
);


--
-- Name: quote_invoice_conversions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quote_invoice_conversions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quote_invoice_conversions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quote_invoice_conversions_id_seq OWNED BY public.quote_invoice_conversions.id;


--
-- Name: quote_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quote_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255) NOT NULL,
    template_items json,
    service_config json,
    pricing_config json,
    tax_config json,
    terms_conditions text,
    is_active boolean DEFAULT true NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    CONSTRAINT quote_templates_category_check CHECK (((category)::text = ANY (ARRAY[('basic'::character varying)::text, ('standard'::character varying)::text, ('premium'::character varying)::text, ('enterprise'::character varying)::text, ('custom'::character varying)::text, ('equipment'::character varying)::text, ('maintenance'::character varying)::text, ('professional'::character varying)::text, ('managed'::character varying)::text])))
);


--
-- Name: quote_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quote_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quote_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quote_templates_id_seq OWNED BY public.quote_templates.id;


--
-- Name: quote_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quote_versions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    quote_id bigint NOT NULL,
    created_by bigint,
    version_number integer NOT NULL,
    quote_data json NOT NULL,
    changes json,
    change_reason character varying(500),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone
);


--
-- Name: quote_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quote_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quote_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quote_versions_id_seq OWNED BY public.quote_versions.id;


--
-- Name: quotes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.quotes (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    prefix character varying(255),
    number integer NOT NULL,
    scope character varying(255),
    status character varying(255) NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    date date NOT NULL,
    expire date,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency_code character varying(3) NOT NULL,
    note text,
    url_key character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    approval_status character varying(255) DEFAULT 'not_required'::character varying NOT NULL,
    sent_at timestamp(0) without time zone,
    created_by bigint,
    archived_at timestamp(0) without time zone,
    category_id bigint NOT NULL,
    client_id bigint NOT NULL,
    CONSTRAINT quotes_approval_status_check CHECK (((approval_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('manager_approved'::character varying)::text, ('executive_approved'::character varying)::text, ('rejected'::character varying)::text, ('not_required'::character varying)::text])))
);


--
-- Name: quotes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.quotes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.quotes_id_seq OWNED BY public.quotes.id;


--
-- Name: rate_cards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rate_cards (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    service_type character varying(255) DEFAULT 'standard'::character varying NOT NULL,
    hourly_rate numeric(10,2) NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    applies_to_all_services boolean DEFAULT false NOT NULL,
    minimum_hours numeric(5,2),
    rounding_increment integer,
    rounding_method character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: rate_cards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rate_cards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rate_cards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rate_cards_id_seq OWNED BY public.rate_cards.id;


--
-- Name: recurring; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.recurring (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    prefix character varying(255),
    number integer NOT NULL,
    scope character varying(255),
    frequency character varying(255) NOT NULL,
    last_sent date,
    next_date date NOT NULL,
    status boolean DEFAULT true NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency_code character varying(3) NOT NULL,
    note text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    auto_invoice_generation boolean DEFAULT true NOT NULL,
    archived_at timestamp(0) without time zone,
    category_id bigint NOT NULL,
    client_id bigint NOT NULL,
    invoice_terms_days integer DEFAULT 30 NOT NULL,
    email_invoice boolean DEFAULT true NOT NULL,
    email_template character varying(255),
    overage_rates json
);


--
-- Name: recurring_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.recurring_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: recurring_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.recurring_id_seq OWNED BY public.recurring.id;


--
-- Name: recurring_invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.recurring_invoices (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    start_date date,
    end_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: recurring_invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.recurring_invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: recurring_invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.recurring_invoices_id_seq OWNED BY public.recurring_invoices.id;


--
-- Name: recurring_tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.recurring_tickets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    template_id bigint,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    interval_value integer DEFAULT 1 NOT NULL,
    next_run timestamp(0) without time zone NOT NULL,
    last_run timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    configuration json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    next_run_date date,
    last_run_date date,
    name character varying(255),
    frequency_config json,
    end_date date,
    max_occurrences integer,
    occurrences_count integer DEFAULT 0 NOT NULL,
    template_overrides json,
    CONSTRAINT recurring_tickets_frequency_check CHECK (((frequency)::text = ANY (ARRAY[('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('annually'::character varying)::text]))),
    CONSTRAINT recurring_tickets_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('paused'::character varying)::text, ('completed'::character varying)::text])))
);


--
-- Name: recurring_tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.recurring_tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: recurring_tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.recurring_tickets_id_seq OWNED BY public.recurring_tickets.id;


--
-- Name: refund_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.refund_requests (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    request_number character varying(255),
    deleted_at timestamp(0) without time zone,
    client_id bigint,
    requested_by bigint,
    refund_type character varying(255) DEFAULT 'credit_refund'::character varying NOT NULL,
    refund_method character varying(255) DEFAULT 'bank_transfer'::character varying NOT NULL,
    requested_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    requested_at timestamp(0) without time zone
);


--
-- Name: refund_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.refund_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: refund_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.refund_requests_id_seq OWNED BY public.refund_requests.id;


--
-- Name: refund_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.refund_transactions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    refund_request_id bigint,
    processed_by bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    currency_code character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    transaction_id character varying(255),
    initiated_at timestamp(0) without time zone,
    max_retries integer DEFAULT 3 NOT NULL
);


--
-- Name: refund_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.refund_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: refund_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.refund_transactions_id_seq OWNED BY public.refund_transactions.id;


--
-- Name: report_exports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_exports (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    report_name character varying(255) NOT NULL,
    format character varying(255) DEFAULT 'pdf'::character varying NOT NULL,
    file_path character varying(255),
    file_size integer,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    generated_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    parameters json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT report_exports_format_check CHECK (((format)::text = ANY (ARRAY[('pdf'::character varying)::text, ('excel'::character varying)::text, ('csv'::character varying)::text]))),
    CONSTRAINT report_exports_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('processing'::character varying)::text, ('completed'::character varying)::text, ('failed'::character varying)::text])))
);


--
-- Name: report_exports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_exports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_exports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_exports_id_seq OWNED BY public.report_exports.id;


--
-- Name: report_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_metrics (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    metric_name character varying(255) NOT NULL,
    metric_type character varying(255) NOT NULL,
    value numeric(15,4) NOT NULL,
    dimensions json,
    metric_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: report_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_metrics_id_seq OWNED BY public.report_metrics.id;


--
-- Name: report_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_subscriptions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    template_id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    recipients json NOT NULL,
    filters json NOT NULL,
    format character varying(255) DEFAULT 'pdf'::character varying NOT NULL,
    delivery_time time(0) without time zone DEFAULT '09:00:00'::time without time zone NOT NULL,
    next_run timestamp(0) without time zone NOT NULL,
    last_run timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT report_subscriptions_format_check CHECK (((format)::text = ANY (ARRAY[('pdf'::character varying)::text, ('excel'::character varying)::text, ('csv'::character varying)::text]))),
    CONSTRAINT report_subscriptions_frequency_check CHECK (((frequency)::text = ANY (ARRAY[('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('quarterly'::character varying)::text])))
);


--
-- Name: report_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_subscriptions_id_seq OWNED BY public.report_subscriptions.id;


--
-- Name: report_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    configuration json NOT NULL,
    default_filters json,
    type character varying(255) DEFAULT 'table'::character varying NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT report_templates_type_check CHECK (((type)::text = ANY (ARRAY[('table'::character varying)::text, ('chart'::character varying)::text, ('summary'::character varying)::text, ('dashboard'::character varying)::text])))
);


--
-- Name: report_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_templates_id_seq OWNED BY public.report_templates.id;


--
-- Name: revenue_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.revenue_metrics (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: revenue_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revenue_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: revenue_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.revenue_metrics_id_seq OWNED BY public.revenue_metrics.id;


--
-- Name: rmm_client_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rmm_client_mappings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    client_id bigint NOT NULL,
    integration_id bigint NOT NULL,
    rmm_client_id character varying(255) NOT NULL,
    rmm_client_name character varying(255) NOT NULL,
    rmm_client_data json,
    is_active boolean DEFAULT true NOT NULL,
    last_sync_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: rmm_client_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rmm_client_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rmm_client_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rmm_client_mappings_id_seq OWNED BY public.rmm_client_mappings.id;


--
-- Name: rmm_integrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rmm_integrations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    rmm_type character varying(255) DEFAULT 'TRMM'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    api_url_encrypted text NOT NULL,
    api_key_encrypted text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_sync_at timestamp(0) without time zone,
    settings json,
    total_agents integer DEFAULT 0 NOT NULL,
    last_alerts_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT rmm_integrations_rmm_type_check CHECK (((rmm_type)::text = 'TRMM'::text))
);


--
-- Name: rmm_integrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rmm_integrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rmm_integrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rmm_integrations_id_seq OWNED BY public.rmm_integrations.id;


--
-- Name: saved_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.saved_reports (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    template_id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    filters json NOT NULL,
    configuration json NOT NULL,
    is_shared boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: saved_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.saved_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: saved_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.saved_reports_id_seq OWNED BY public.saved_reports.id;


--
-- Name: scheduler_coordination; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scheduler_coordination (
    id bigint NOT NULL,
    job_name character varying(100) NOT NULL,
    schedule_key character varying(150) NOT NULL,
    server_id character varying(100) NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    heartbeat_at timestamp(0) without time zone NOT NULL,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: scheduler_coordination_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scheduler_coordination_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scheduler_coordination_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scheduler_coordination_id_seq OWNED BY public.scheduler_coordination.id;


--
-- Name: service_tax_rates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_tax_rates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    tax_jurisdiction_id bigint NOT NULL,
    tax_category_id bigint NOT NULL,
    service_type character varying(50) NOT NULL,
    tax_type character varying(255) NOT NULL,
    tax_name character varying(255) NOT NULL,
    authority_name character varying(255) NOT NULL,
    tax_code character varying(50),
    description text,
    regulatory_code character varying(50),
    rate_type character varying(255) NOT NULL,
    percentage_rate numeric(8,6),
    fixed_amount numeric(10,2),
    minimum_threshold numeric(10,2),
    maximum_amount numeric(10,2),
    calculation_method character varying(255) NOT NULL,
    service_types json,
    conditions json,
    is_active boolean DEFAULT true NOT NULL,
    is_recoverable boolean DEFAULT false NOT NULL,
    is_compound boolean DEFAULT false NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    effective_date date NOT NULL,
    expiry_date date,
    external_id character varying(255),
    source character varying(255),
    last_updated_from_source timestamp(0) without time zone,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: service_tax_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_tax_rates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_tax_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_tax_rates_id_seq OWNED BY public.service_tax_rates.id;


--
-- Name: services; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.services (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    service_type character varying(255) DEFAULT 'custom'::character varying NOT NULL,
    estimated_hours numeric(8,2),
    sla_days integer,
    response_time_hours integer,
    resolution_time_hours integer,
    deliverables json,
    dependencies json,
    requirements json,
    requires_scheduling boolean DEFAULT false NOT NULL,
    min_notice_hours integer DEFAULT 24 NOT NULL,
    duration_minutes integer,
    availability_schedule json,
    default_assignee_id bigint,
    required_skills json,
    required_resources json,
    has_setup_fee boolean DEFAULT false NOT NULL,
    setup_fee numeric(10,2),
    has_cancellation_fee boolean DEFAULT false NOT NULL,
    cancellation_fee numeric(10,2),
    cancellation_notice_hours integer DEFAULT 24 NOT NULL,
    minimum_commitment_months integer,
    maximum_duration_months integer,
    auto_renew boolean DEFAULT false NOT NULL,
    renewal_notice_days integer DEFAULT 30 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT services_service_type_check CHECK (((service_type)::text = ANY (ARRAY[('consulting'::character varying)::text, ('support'::character varying)::text, ('maintenance'::character varying)::text, ('development'::character varying)::text, ('training'::character varying)::text, ('implementation'::character varying)::text, ('custom'::character varying)::text])))
);


--
-- Name: services_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.services_id_seq OWNED BY public.services.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    current_database_version character varying(10) NOT NULL,
    start_page character varying(255) DEFAULT 'clients.php'::character varying NOT NULL,
    smtp_host character varying(255),
    smtp_port integer,
    smtp_encryption character varying(255),
    smtp_username character varying(255),
    smtp_password text,
    mail_from_email character varying(255),
    mail_from_name character varying(255),
    imap_host character varying(255),
    imap_port integer,
    imap_encryption character varying(255),
    imap_username character varying(255),
    imap_password text,
    default_transfer_from_account integer,
    default_transfer_to_account integer,
    default_payment_account integer,
    default_expense_account integer,
    default_payment_method character varying(255),
    default_expense_payment_method character varying(255),
    default_calendar integer,
    default_net_terms integer,
    default_hourly_rate numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    invoice_prefix character varying(255),
    invoice_next_number integer,
    invoice_footer text,
    invoice_from_name character varying(255),
    invoice_from_email character varying(255),
    invoice_late_fee_enable boolean DEFAULT false NOT NULL,
    invoice_late_fee_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    quote_prefix character varying(255),
    quote_next_number integer,
    quote_footer text,
    quote_from_name character varying(255),
    quote_from_email character varying(255),
    ticket_prefix character varying(255),
    ticket_next_number integer,
    ticket_from_name character varying(255),
    ticket_from_email character varying(255),
    ticket_email_parse boolean DEFAULT false NOT NULL,
    ticket_client_general_notifications boolean DEFAULT true NOT NULL,
    ticket_autoclose boolean DEFAULT false NOT NULL,
    ticket_autoclose_hours integer DEFAULT 72 NOT NULL,
    ticket_new_ticket_notification_email character varying(255),
    enable_cron boolean DEFAULT false NOT NULL,
    cron_key character varying(255),
    recurring_auto_send_invoice boolean DEFAULT true NOT NULL,
    enable_alert_domain_expire boolean DEFAULT true NOT NULL,
    send_invoice_reminders boolean DEFAULT true NOT NULL,
    invoice_overdue_reminders character varying(255),
    theme character varying(255) DEFAULT 'blue'::character varying NOT NULL,
    telemetry boolean DEFAULT false NOT NULL,
    timezone character varying(255) DEFAULT 'America/New_York'::character varying NOT NULL,
    destructive_deletes_enable boolean DEFAULT false NOT NULL,
    module_enable_itdoc boolean DEFAULT true NOT NULL,
    module_enable_accounting boolean DEFAULT true NOT NULL,
    module_enable_ticketing boolean DEFAULT true NOT NULL,
    client_portal_enable boolean DEFAULT true NOT NULL,
    portal_branding_settings json,
    portal_customization_settings json,
    portal_access_controls json,
    portal_feature_toggles json,
    portal_self_service_tickets boolean DEFAULT true NOT NULL,
    portal_knowledge_base_access boolean DEFAULT true NOT NULL,
    portal_invoice_access boolean DEFAULT true NOT NULL,
    portal_payment_processing boolean DEFAULT false NOT NULL,
    portal_asset_visibility boolean DEFAULT false NOT NULL,
    portal_sso_settings json,
    portal_mobile_settings json,
    portal_dashboard_settings json,
    login_message text,
    login_key_required boolean DEFAULT false NOT NULL,
    login_key_secret character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    date_format character varying(20) DEFAULT 'Y-m-d'::character varying NOT NULL,
    company_logo character varying(255),
    company_colors json,
    company_address character varying(255),
    company_city character varying(255),
    company_state character varying(255),
    company_zip character varying(255),
    company_country character varying(255) DEFAULT 'US'::character varying NOT NULL,
    company_phone character varying(255),
    company_website character varying(255),
    company_tax_id character varying(255),
    business_hours json,
    company_holidays json,
    company_language character varying(255) DEFAULT 'en'::character varying NOT NULL,
    company_currency character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    custom_fields json,
    localization_settings json,
    password_min_length integer DEFAULT 8 NOT NULL,
    password_require_special boolean DEFAULT true NOT NULL,
    password_require_numbers boolean DEFAULT true NOT NULL,
    password_require_uppercase boolean DEFAULT true NOT NULL,
    password_expiry_days integer DEFAULT 90 NOT NULL,
    password_history_count integer DEFAULT 5 NOT NULL,
    two_factor_enabled boolean DEFAULT false NOT NULL,
    two_factor_methods json,
    session_timeout_minutes integer DEFAULT 480 NOT NULL,
    force_single_session boolean DEFAULT false NOT NULL,
    max_login_attempts integer DEFAULT 5 NOT NULL,
    lockout_duration_minutes integer DEFAULT 15 NOT NULL,
    allowed_ip_ranges json,
    blocked_ip_ranges json,
    geo_blocking_enabled boolean DEFAULT false NOT NULL,
    allowed_countries json,
    sso_settings json,
    audit_logging_enabled boolean DEFAULT true NOT NULL,
    audit_retention_days integer DEFAULT 365 NOT NULL,
    smtp_auth_required boolean DEFAULT true NOT NULL,
    smtp_use_tls boolean DEFAULT true NOT NULL,
    smtp_timeout integer DEFAULT 30 NOT NULL,
    email_retry_attempts integer DEFAULT 3 NOT NULL,
    email_templates json,
    email_signatures json,
    email_tracking_enabled boolean DEFAULT false NOT NULL,
    sms_settings json,
    voice_settings json,
    slack_settings json,
    teams_settings json,
    discord_settings json,
    video_conferencing_settings json,
    communication_preferences json,
    quiet_hours_start time(0) without time zone,
    quiet_hours_end time(0) without time zone,
    multi_currency_enabled boolean DEFAULT false NOT NULL,
    supported_currencies json,
    exchange_rate_provider character varying(255),
    auto_update_exchange_rates boolean DEFAULT true NOT NULL,
    tax_calculation_settings json,
    tax_engine_provider character varying(255),
    payment_gateway_settings json,
    stripe_settings json,
    square_settings json,
    paypal_settings json,
    authorize_net_settings json,
    ach_settings json,
    recurring_billing_enabled boolean DEFAULT true NOT NULL,
    recurring_billing_settings json,
    late_fee_settings json,
    collection_settings json,
    accounting_integration_settings json,
    quickbooks_settings json,
    xero_settings json,
    sage_settings json,
    revenue_recognition_enabled boolean DEFAULT false NOT NULL,
    revenue_recognition_settings json,
    purchase_order_settings json,
    expense_approval_settings json,
    connectwise_automate_settings json,
    datto_rmm_settings json,
    ninja_rmm_settings json,
    kaseya_vsa_settings json,
    auvik_settings json,
    prtg_settings json,
    solarwinds_settings json,
    monitoring_alert_thresholds json,
    escalation_rules json,
    asset_discovery_settings json,
    patch_management_settings json,
    remote_access_settings json,
    auto_create_tickets_from_alerts boolean DEFAULT false NOT NULL,
    alert_to_ticket_mapping json,
    ticket_categorization_rules json,
    ticket_priority_rules json,
    sla_definitions json,
    sla_escalation_policies json,
    auto_assignment_rules json,
    routing_logic json,
    approval_workflows json,
    time_tracking_enabled boolean DEFAULT true NOT NULL,
    time_tracking_settings json,
    customer_satisfaction_enabled boolean DEFAULT false NOT NULL,
    csat_settings json,
    ticket_templates json,
    ticket_automation_rules json,
    multichannel_settings json,
    queue_management_settings json,
    remember_me_enabled boolean DEFAULT true NOT NULL,
    wire_settings json,
    check_settings json,
    imap_auth_method character varying(255),
    hr_settings json,
    CONSTRAINT settings_imap_auth_method_check CHECK (((imap_auth_method)::text = ANY (ARRAY[('password'::character varying)::text, ('oauth'::character varying)::text, ('token'::character varying)::text])))
);


--
-- Name: settings_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings_configurations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    domain character varying(50) NOT NULL,
    category character varying(50) NOT NULL,
    settings json NOT NULL,
    metadata json,
    is_active boolean DEFAULT true NOT NULL,
    last_modified_at timestamp(0) without time zone,
    last_modified_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: settings_configurations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_configurations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_configurations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_configurations_id_seq OWNED BY public.settings_configurations.id;


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: shifts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shifts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    start_time time(0) without time zone NOT NULL,
    end_time time(0) without time zone NOT NULL,
    break_minutes integer DEFAULT 0 NOT NULL,
    days_of_week json NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    color character varying(255) DEFAULT '#3B82F6'::character varying NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.shifts_id_seq OWNED BY public.shifts.id;


--
-- Name: slas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.slas (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    critical_response_minutes integer DEFAULT 60 NOT NULL,
    high_response_minutes integer DEFAULT 240 NOT NULL,
    medium_response_minutes integer DEFAULT 480 NOT NULL,
    low_response_minutes integer DEFAULT 1440 NOT NULL,
    critical_resolution_minutes integer DEFAULT 240 NOT NULL,
    high_resolution_minutes integer DEFAULT 1440 NOT NULL,
    medium_resolution_minutes integer DEFAULT 4320 NOT NULL,
    low_resolution_minutes integer DEFAULT 10080 NOT NULL,
    business_hours_start time(0) without time zone DEFAULT '09:00:00'::time without time zone NOT NULL,
    business_hours_end time(0) without time zone DEFAULT '17:00:00'::time without time zone NOT NULL,
    business_days json DEFAULT '["monday","tuesday","wednesday","thursday","friday"]'::json NOT NULL,
    timezone character varying(255) DEFAULT 'UTC'::character varying NOT NULL,
    coverage_type character varying(255) DEFAULT 'business_hours'::character varying NOT NULL,
    holiday_coverage boolean DEFAULT false NOT NULL,
    exclude_weekends boolean DEFAULT true NOT NULL,
    escalation_enabled boolean DEFAULT true NOT NULL,
    escalation_levels json,
    breach_warning_percentage integer DEFAULT 80 NOT NULL,
    uptime_percentage numeric(5,2) DEFAULT 99.5 NOT NULL,
    first_call_resolution_target numeric(5,2) DEFAULT '75'::numeric NOT NULL,
    customer_satisfaction_target numeric(5,2) DEFAULT '90'::numeric NOT NULL,
    notify_on_breach boolean DEFAULT true NOT NULL,
    notify_on_warning boolean DEFAULT true NOT NULL,
    notification_emails json,
    effective_from date DEFAULT '2025-10-26'::date NOT NULL,
    effective_to date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT slas_coverage_type_check CHECK (((coverage_type)::text = ANY (ARRAY[('24/7'::character varying)::text, ('business_hours'::character varying)::text, ('custom'::character varying)::text])))
);


--
-- Name: slas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.slas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: slas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.slas_id_seq OWNED BY public.slas.id;


--
-- Name: subscription_plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscription_plans (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(50) NOT NULL,
    stripe_price_id character varying(255),
    stripe_price_id_yearly character varying(255),
    price_monthly numeric(10,2) NOT NULL,
    price_yearly numeric(10,2),
    price_per_user_monthly numeric(10,2),
    pricing_model character varying(255) DEFAULT 'per_user'::character varying NOT NULL,
    minimum_users integer DEFAULT 1 NOT NULL,
    base_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    user_limit integer,
    max_users integer,
    max_clients integer,
    features json,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT subscription_plans_pricing_model_check CHECK (((pricing_model)::text = ANY (ARRAY[('fixed'::character varying)::text, ('per_user'::character varying)::text, ('hybrid'::character varying)::text])))
);


--
-- Name: subscription_plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscription_plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscription_plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscription_plans_id_seq OWNED BY public.subscription_plans.id;


--
-- Name: subsidiary_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subsidiary_permissions (
    id bigint NOT NULL,
    granter_company_id bigint NOT NULL,
    grantee_company_id bigint NOT NULL,
    user_id bigint,
    resource_type character varying(255) NOT NULL,
    permission_type character varying(255) NOT NULL,
    conditions json,
    scope character varying(255) DEFAULT 'all'::character varying NOT NULL,
    scope_filters json,
    resource_ids text,
    is_inherited boolean DEFAULT false NOT NULL,
    inherited_from character varying(255),
    can_delegate boolean DEFAULT false NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    expires_at timestamp(0) without time zone,
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT subsidiary_permissions_scope_check CHECK (((scope)::text = ANY (ARRAY[('all'::character varying)::text, ('specific'::character varying)::text, ('filtered'::character varying)::text])))
);


--
-- Name: subsidiary_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subsidiary_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subsidiary_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subsidiary_permissions_id_seq OWNED BY public.subsidiary_permissions.id;


--
-- Name: suspicious_login_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.suspicious_login_attempts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    ip_address character varying(45) NOT NULL,
    verification_token character varying(64) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    location_data json,
    device_fingerprint json,
    user_agent text,
    trusted_location_requested boolean DEFAULT false NOT NULL,
    risk_score smallint DEFAULT '0'::smallint NOT NULL,
    detection_reasons json,
    approved_at timestamp(0) without time zone,
    denied_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone NOT NULL,
    notification_sent_at timestamp(0) without time zone,
    approval_ip character varying(45),
    approval_user_agent text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT suspicious_login_attempts_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('denied'::character varying)::text, ('expired'::character varying)::text])))
);


--
-- Name: suspicious_login_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.suspicious_login_attempts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: suspicious_login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.suspicious_login_attempts_id_seq OWNED BY public.suspicious_login_attempts.id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type integer DEFAULT 1 NOT NULL,
    color character varying(7),
    icon character varying(50),
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone
);


--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: task_checklist_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.task_checklist_items (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    task_id bigint NOT NULL,
    description character varying(255) NOT NULL,
    is_completed boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    completed_at timestamp(0) without time zone,
    completed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: task_checklist_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.task_checklist_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: task_checklist_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.task_checklist_items_id_seq OWNED BY public.task_checklist_items.id;


--
-- Name: task_dependencies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.task_dependencies (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    task_id bigint NOT NULL,
    depends_on_task_id bigint NOT NULL,
    dependency_type character varying(255) DEFAULT 'finish_to_start'::character varying NOT NULL,
    lag_days integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT task_dependencies_dependency_type_check CHECK (((dependency_type)::text = ANY (ARRAY[('finish_to_start'::character varying)::text, ('start_to_start'::character varying)::text, ('finish_to_finish'::character varying)::text, ('start_to_finish'::character varying)::text])))
);


--
-- Name: task_dependencies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.task_dependencies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: task_dependencies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.task_dependencies_id_seq OWNED BY public.task_dependencies.id;


--
-- Name: task_watchers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.task_watchers (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    task_id bigint NOT NULL,
    user_id bigint NOT NULL,
    email_notifications boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: task_watchers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.task_watchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: task_watchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.task_watchers_id_seq OWNED BY public.task_watchers.id;


--
-- Name: tax_api_query_cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_api_query_cache (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    api_provider character varying(255) NOT NULL,
    query_type character varying(255) NOT NULL,
    query_hash character varying(255) NOT NULL,
    query_parameters json NOT NULL,
    api_response json NOT NULL,
    api_called_at timestamp(0) without time zone NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    status character varying(255) NOT NULL,
    error_message text,
    response_time_ms numeric(10,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: tax_api_query_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_api_query_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_api_query_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_api_query_cache_id_seq OWNED BY public.tax_api_query_cache.id;


--
-- Name: tax_api_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_api_settings (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    provider character varying(255) NOT NULL,
    enabled boolean DEFAULT false NOT NULL,
    credentials text NOT NULL,
    configuration json NOT NULL,
    monthly_api_calls integer DEFAULT 0 NOT NULL,
    monthly_limit integer,
    last_api_call timestamp(0) without time zone,
    monthly_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) NOT NULL,
    last_error text,
    last_health_check timestamp(0) without time zone,
    health_data json,
    audit_log json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tax_api_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_api_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_api_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_api_settings_id_seq OWNED BY public.tax_api_settings.id;


--
-- Name: tax_calculations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_calculations (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    calculable_type character varying(255),
    calculable_id bigint,
    calculation_id character varying(255) NOT NULL,
    engine_type character varying(255) NOT NULL,
    category_type character varying(255),
    calculation_type character varying(255) NOT NULL,
    base_amount numeric(15,2) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    input_parameters json NOT NULL,
    customer_data json,
    service_address json,
    total_tax_amount numeric(15,2) NOT NULL,
    final_amount numeric(15,2) NOT NULL,
    effective_tax_rate numeric(8,6) NOT NULL,
    tax_breakdown json NOT NULL,
    api_enhancements json,
    jurisdictions json,
    exemptions_applied json,
    engine_metadata json NOT NULL,
    api_calls_made json,
    validated boolean DEFAULT false NOT NULL,
    validated_at timestamp(0) without time zone,
    validated_by bigint,
    validation_notes text,
    status character varying(255) NOT NULL,
    status_history json,
    created_by bigint,
    updated_by bigint,
    change_log json,
    calculation_time_ms integer,
    api_calls_count integer DEFAULT 0 NOT NULL,
    api_cost numeric(10,4) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tax_calculations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_calculations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_calculations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_calculations_id_seq OWNED BY public.tax_calculations.id;


--
-- Name: tax_exemptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_exemptions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    priority integer DEFAULT 0 NOT NULL,
    created_by bigint,
    updated_by bigint,
    deleted_at timestamp(0) without time zone
);


--
-- Name: tax_exemptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_exemptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_exemptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_exemptions_id_seq OWNED BY public.tax_exemptions.id;


--
-- Name: tax_jurisdictions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_jurisdictions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    jurisdiction_type character varying(255) NOT NULL,
    authority_name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: tax_jurisdictions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_jurisdictions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_jurisdictions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_jurisdictions_id_seq OWNED BY public.tax_jurisdictions.id;


--
-- Name: tax_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_profiles (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    category_id bigint,
    tax_category_id bigint,
    profile_type character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    required_fields json NOT NULL,
    tax_types json NOT NULL,
    calculation_engine character varying(255) NOT NULL,
    field_definitions json,
    validation_rules json,
    default_values json,
    is_active boolean DEFAULT true NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: tax_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tax_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tax_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tax_profiles_id_seq OWNED BY public.tax_profiles.id;


--
-- Name: taxes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.taxes (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    percent double precision NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone
);


--
-- Name: taxes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.taxes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: taxes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.taxes_id_seq OWNED BY public.taxes.id;


--
-- Name: ticket_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_assignments (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    company_id bigint NOT NULL,
    assigned_to bigint,
    assigned_by bigint NOT NULL,
    assigned_at timestamp(0) without time zone NOT NULL,
    unassigned_at timestamp(0) without time zone,
    notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_assignments_id_seq OWNED BY public.ticket_assignments.id;


--
-- Name: ticket_calendar_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_calendar_events (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    start_time timestamp(0) without time zone NOT NULL,
    end_time timestamp(0) without time zone NOT NULL,
    all_day boolean DEFAULT false NOT NULL,
    attendees json,
    location character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    attendee_emails json,
    is_onsite boolean DEFAULT false NOT NULL,
    is_all_day boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    notes text,
    reminders json,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT ticket_calendar_events_status_check CHECK (((status)::text = ANY (ARRAY[('scheduled'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text, ('rescheduled'::character varying)::text])))
);


--
-- Name: ticket_calendar_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_calendar_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_calendar_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_calendar_events_id_seq OWNED BY public.ticket_calendar_events.id;


--
-- Name: ticket_comment_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_comment_attachments (
    id bigint NOT NULL,
    ticket_comment_id bigint NOT NULL,
    company_id bigint NOT NULL,
    filename character varying(255) NOT NULL,
    original_filename character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    size bigint NOT NULL,
    content text NOT NULL,
    uploaded_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_comment_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_comment_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_comment_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_comment_attachments_id_seq OWNED BY public.ticket_comment_attachments.id;


--
-- Name: ticket_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_comments (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    company_id bigint NOT NULL,
    content text NOT NULL,
    visibility character varying(255) DEFAULT 'public'::character varying NOT NULL,
    source character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    author_id bigint,
    author_type character varying(255) DEFAULT 'user'::character varying NOT NULL,
    metadata json,
    parent_id bigint,
    is_resolution boolean DEFAULT false NOT NULL,
    time_entry_id bigint,
    sentiment_score numeric(3,2),
    sentiment_label character varying(255),
    sentiment_analyzed_at timestamp(0) without time zone,
    sentiment_confidence numeric(3,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT ticket_comments_author_type_check CHECK (((author_type)::text = ANY (ARRAY[('user'::character varying)::text, ('system'::character varying)::text, ('workflow'::character varying)::text, ('customer'::character varying)::text]))),
    CONSTRAINT ticket_comments_sentiment_label_check CHECK (((sentiment_label)::text = ANY (ARRAY[('POSITIVE'::character varying)::text, ('WEAK_POSITIVE'::character varying)::text, ('NEUTRAL'::character varying)::text, ('WEAK_NEGATIVE'::character varying)::text, ('NEGATIVE'::character varying)::text]))),
    CONSTRAINT ticket_comments_source_check CHECK (((source)::text = ANY (ARRAY[('manual'::character varying)::text, ('workflow'::character varying)::text, ('system'::character varying)::text, ('api'::character varying)::text, ('email'::character varying)::text]))),
    CONSTRAINT ticket_comments_visibility_check CHECK (((visibility)::text = ANY (ARRAY[('public'::character varying)::text, ('internal'::character varying)::text])))
);


--
-- Name: ticket_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_comments_id_seq OWNED BY public.ticket_comments.id;


--
-- Name: ticket_priority_queue; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_priority_queue (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    priority_score integer NOT NULL,
    queue_time timestamp(0) without time zone NOT NULL,
    scoring_factors json NOT NULL,
    is_escalated boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_priority_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_priority_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_priority_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_priority_queue_id_seq OWNED BY public.ticket_priority_queue.id;


--
-- Name: ticket_priority_queues; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_priority_queues (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    company_id bigint NOT NULL,
    queue_position integer DEFAULT 1 NOT NULL,
    priority_score numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    escalation_level integer DEFAULT 0 NOT NULL,
    assigned_team character varying(255),
    sla_deadline timestamp(0) without time zone,
    escalated_at timestamp(0) without time zone,
    escalation_rules json,
    escalation_reason text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_priority_queues_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_priority_queues_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_priority_queues_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_priority_queues_id_seq OWNED BY public.ticket_priority_queues.id;


--
-- Name: ticket_ratings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_ratings (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint,
    client_id bigint NOT NULL,
    company_id bigint NOT NULL,
    rating integer NOT NULL,
    feedback text,
    rating_type character varying(255) DEFAULT 'satisfaction'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN ticket_ratings.rating; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_ratings.rating IS 'Rating from 1-5';


--
-- Name: COLUMN ticket_ratings.rating_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_ratings.rating_type IS 'satisfaction, resolution, communication, etc';


--
-- Name: ticket_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_ratings_id_seq OWNED BY public.ticket_ratings.id;


--
-- Name: ticket_replies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_replies (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    reply text NOT NULL,
    type character varying(10) NOT NULL,
    time_worked time(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sentiment_score numeric(3,2),
    sentiment_label character varying(255),
    sentiment_analyzed_at timestamp(0) without time zone,
    sentiment_confidence numeric(3,2),
    archived_at timestamp(0) without time zone,
    replied_by bigint NOT NULL,
    ticket_id bigint NOT NULL,
    CONSTRAINT ticket_replies_sentiment_label_check CHECK (((sentiment_label)::text = ANY (ARRAY[('POSITIVE'::character varying)::text, ('WEAK_POSITIVE'::character varying)::text, ('NEUTRAL'::character varying)::text, ('WEAK_NEGATIVE'::character varying)::text, ('NEGATIVE'::character varying)::text])))
);


--
-- Name: COLUMN ticket_replies.sentiment_score; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_replies.sentiment_score IS 'Sentiment score from -1.00 (negative) to 1.00 (positive)';


--
-- Name: COLUMN ticket_replies.sentiment_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_replies.sentiment_label IS 'Sentiment classification label';


--
-- Name: COLUMN ticket_replies.sentiment_analyzed_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_replies.sentiment_analyzed_at IS 'When sentiment analysis was performed';


--
-- Name: COLUMN ticket_replies.sentiment_confidence; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ticket_replies.sentiment_confidence IS 'Confidence score for sentiment analysis (0.00 to 1.00)';


--
-- Name: ticket_replies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_replies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_replies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_replies_id_seq OWNED BY public.ticket_replies.id;


--
-- Name: ticket_status_transitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_status_transitions (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    from_status character varying(255) NOT NULL,
    to_status character varying(255) NOT NULL,
    transition_name character varying(255) NOT NULL,
    requires_approval boolean DEFAULT false NOT NULL,
    allowed_roles json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_status_transitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_status_transitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_status_transitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_status_transitions_id_seq OWNED BY public.ticket_status_transitions.id;


--
-- Name: ticket_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    default_fields json,
    instructions text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_templates_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text])))
);


--
-- Name: ticket_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_templates_id_seq OWNED BY public.ticket_templates.id;


--
-- Name: ticket_time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_time_entries (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint NOT NULL,
    description text NOT NULL,
    start_time timestamp(0) without time zone,
    end_time timestamp(0) without time zone,
    minutes integer,
    hourly_rate numeric(10,2),
    is_billable boolean DEFAULT true NOT NULL,
    is_billed boolean DEFAULT false NOT NULL,
    entry_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    started_at timestamp(0) without time zone,
    ended_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    hours_worked numeric(8,2),
    minutes_worked integer,
    hours_billed numeric(8,2),
    work_performed text,
    work_date date,
    billable boolean DEFAULT true NOT NULL,
    work_type character varying(255),
    rate_type character varying(255),
    status character varying(255) DEFAULT 'submitted'::character varying NOT NULL,
    submitted_at timestamp(0) without time zone,
    submitted_by bigint,
    approved_at timestamp(0) without time zone,
    approved_by bigint,
    rejected_at timestamp(0) without time zone,
    rejection_reason text,
    amount numeric(10,2),
    metadata json,
    CONSTRAINT ticket_time_entries_entry_type_check CHECK (((entry_type)::text = ANY (ARRAY[('manual'::character varying)::text, ('timer'::character varying)::text])))
);


--
-- Name: ticket_time_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_time_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_time_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_time_entries_id_seq OWNED BY public.ticket_time_entries.id;


--
-- Name: ticket_watchers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_watchers (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint,
    email character varying(255) NOT NULL,
    added_by bigint,
    notification_preferences json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_watchers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_watchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_watchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_watchers_id_seq OWNED BY public.ticket_watchers.id;


--
-- Name: ticket_workflows; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_workflows (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    conditions json NOT NULL,
    actions json NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_workflows_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_workflows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_workflows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_workflows_id_seq OWNED BY public.ticket_workflows.id;


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    prefix character varying(255),
    number integer NOT NULL,
    source character varying(255),
    category character varying(255),
    subject character varying(255) NOT NULL,
    details text NOT NULL,
    priority character varying(255),
    status character varying(255) NOT NULL,
    billable boolean DEFAULT false NOT NULL,
    scheduled_at timestamp(0) without time zone,
    onsite boolean DEFAULT false NOT NULL,
    vendor_ticket_number character varying(255),
    feedback character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sentiment_score numeric(3,2),
    sentiment_label character varying(255),
    sentiment_analyzed_at timestamp(0) without time zone,
    sentiment_confidence numeric(3,2),
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    resolution_summary text,
    client_can_reopen boolean DEFAULT true NOT NULL,
    reopened_at timestamp(0) without time zone,
    reopened_by bigint,
    resolution_count integer DEFAULT 0 NOT NULL,
    type character varying(255),
    estimated_resolution_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    created_by bigint NOT NULL,
    assigned_to bigint,
    closed_by bigint,
    vendor_id bigint,
    client_id bigint NOT NULL,
    contact_id bigint,
    location_id bigint,
    asset_id bigint,
    invoice_id bigint,
    project_id bigint,
    CONSTRAINT tickets_sentiment_label_check CHECK (((sentiment_label)::text = ANY (ARRAY[('POSITIVE'::character varying)::text, ('WEAK_POSITIVE'::character varying)::text, ('NEUTRAL'::character varying)::text, ('WEAK_NEGATIVE'::character varying)::text, ('NEGATIVE'::character varying)::text])))
);


--
-- Name: COLUMN tickets.sentiment_score; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.tickets.sentiment_score IS 'Sentiment score from -1.00 (negative) to 1.00 (positive)';


--
-- Name: COLUMN tickets.sentiment_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.tickets.sentiment_label IS 'Sentiment classification label';


--
-- Name: COLUMN tickets.sentiment_analyzed_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.tickets.sentiment_analyzed_at IS 'When sentiment analysis was performed';


--
-- Name: COLUMN tickets.sentiment_confidence; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.tickets.sentiment_confidence IS 'Confidence score for sentiment analysis (0.00 to 1.00)';


--
-- Name: tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tickets_id_seq OWNED BY public.tickets.id;


--
-- Name: time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entries (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    ticket_id bigint,
    project_id bigint,
    client_id bigint,
    hours numeric(5,2) NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    rate numeric(10,2),
    description text,
    date date NOT NULL,
    start_time time(0) without time zone,
    end_time time(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: time_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.time_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: time_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.time_entries_id_seq OWNED BY public.time_entries.id;


--
-- Name: time_entry_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entry_templates (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    work_type character varying(255) NOT NULL,
    default_hours numeric(5,2) NOT NULL,
    category character varying(255),
    keywords json,
    is_active boolean DEFAULT true NOT NULL,
    is_billable boolean DEFAULT true NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: time_entry_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.time_entry_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: time_entry_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.time_entry_templates_id_seq OWNED BY public.time_entry_templates.id;


--
-- Name: time_off_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_off_requests (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    is_full_day boolean DEFAULT true NOT NULL,
    start_time time(0) without time zone,
    end_time time(0) without time zone,
    total_hours integer NOT NULL,
    reason text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by bigint,
    reviewed_at timestamp(0) without time zone,
    review_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT time_off_requests_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('denied'::character varying)::text]))),
    CONSTRAINT time_off_requests_type_check CHECK (((type)::text = ANY (ARRAY[('vacation'::character varying)::text, ('sick'::character varying)::text, ('personal'::character varying)::text, ('unpaid'::character varying)::text, ('holiday'::character varying)::text, ('bereavement'::character varying)::text])))
);


--
-- Name: time_off_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.time_off_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: time_off_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.time_off_requests_id_seq OWNED BY public.time_off_requests.id;


--
-- Name: trusted_devices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trusted_devices (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    user_id bigint NOT NULL,
    device_fingerprint json NOT NULL,
    device_name character varying(255),
    ip_address character varying(45),
    location_data json,
    user_agent text,
    trust_level smallint DEFAULT '50'::smallint NOT NULL,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    verification_method character varying(255) DEFAULT 'email'::character varying NOT NULL,
    created_from_suspicious_login boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT trusted_devices_verification_method_check CHECK (((verification_method)::text = ANY (ARRAY[('email'::character varying)::text, ('sms'::character varying)::text, ('manual'::character varying)::text, ('suspicious_login'::character varying)::text])))
);


--
-- Name: trusted_devices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trusted_devices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trusted_devices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trusted_devices_id_seq OWNED BY public.trusted_devices.id;


--
-- Name: usage_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_alerts (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    client_id bigint,
    alert_name character varying(255),
    alert_code character varying(255),
    alert_type character varying(255) DEFAULT 'threshold'::character varying NOT NULL,
    usage_type character varying(255) DEFAULT 'voice'::character varying NOT NULL,
    threshold_type character varying(255) DEFAULT 'percentage'::character varying NOT NULL,
    threshold_value numeric(10,2) DEFAULT '80'::numeric NOT NULL,
    threshold_unit character varying(255) DEFAULT 'percent'::character varying NOT NULL,
    alert_status character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    alert_created_date timestamp(0) without time zone
);


--
-- Name: usage_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_alerts_id_seq OWNED BY public.usage_alerts.id;


--
-- Name: usage_buckets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_buckets (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    client_id bigint,
    bucket_name character varying(255),
    bucket_code character varying(255),
    bucket_type character varying(255) DEFAULT 'included'::character varying NOT NULL,
    usage_type character varying(255) DEFAULT 'voice'::character varying NOT NULL,
    bucket_capacity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    allocated_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    used_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    capacity_unit character varying(255) DEFAULT 'minutes'::character varying NOT NULL,
    bucket_status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    next_reset_date timestamp(0) without time zone
);


--
-- Name: usage_buckets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_buckets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_buckets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_buckets_id_seq OWNED BY public.usage_buckets.id;


--
-- Name: usage_pools; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_pools (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    cycle_start_date date,
    cycle_end_date date,
    next_reset_date date,
    deleted_at timestamp(0) without time zone,
    pool_code character varying(255) NOT NULL
);


--
-- Name: usage_pools_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_pools_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_pools_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_pools_id_seq OWNED BY public.usage_pools.id;


--
-- Name: usage_records; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_records (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: usage_records_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_records_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_records_id_seq OWNED BY public.usage_records.id;


--
-- Name: usage_tiers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_tiers (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: usage_tiers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_tiers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_tiers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_tiers_id_seq OWNED BY public.usage_tiers.id;


--
-- Name: user_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_clients (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    client_id bigint NOT NULL,
    access_level character varying(255) DEFAULT 'view'::character varying NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    assigned_at date,
    expires_at date,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_clients_id_seq OWNED BY public.user_clients.id;


--
-- Name: user_dashboard_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_dashboard_configs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    company_id bigint NOT NULL,
    dashboard_name character varying(255) DEFAULT 'main'::character varying NOT NULL,
    layout json NOT NULL,
    widgets json NOT NULL,
    preferences json NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    is_shared boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_dashboard_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_dashboard_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_dashboard_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_dashboard_configs_id_seq OWNED BY public.user_dashboard_configs.id;


--
-- Name: user_favorite_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_favorite_clients (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    client_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_favorite_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_favorite_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_favorite_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_favorite_clients_id_seq OWNED BY public.user_favorite_clients.id;


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_roles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    company_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_roles_id_seq OWNED BY public.user_roles.id;


--
-- Name: user_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_settings (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    company_id bigint NOT NULL,
    role integer DEFAULT 1 NOT NULL,
    remember_me_token character varying(255),
    force_mfa boolean DEFAULT false NOT NULL,
    records_per_page integer DEFAULT 10 NOT NULL,
    dashboard_financial_enable boolean DEFAULT false NOT NULL,
    dashboard_technical_enable boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    theme character varying(20) DEFAULT 'light'::character varying NOT NULL,
    preferences json
);


--
-- Name: user_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_settings_id_seq OWNED BY public.user_settings.id;


--
-- Name: user_widget_instances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_widget_instances (
    id bigint NOT NULL,
    user_dashboard_config_id bigint NOT NULL,
    dashboard_widget_id bigint NOT NULL,
    instance_id character varying(255) NOT NULL,
    position_x integer NOT NULL,
    position_y integer NOT NULL,
    width integer NOT NULL,
    height integer NOT NULL,
    custom_config json,
    filters json,
    refresh_interval integer,
    is_visible boolean DEFAULT true NOT NULL,
    is_collapsed boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_widget_instances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_widget_instances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_widget_instances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_widget_instances_id_seq OWNED BY public.user_widget_instances.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    token character varying(255),
    avatar character varying(255),
    specific_encryption_ciphertext character varying(255),
    php_session character varying(255),
    extension_key character varying(18),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    phone character varying(255),
    title character varying(255),
    department character varying(255),
    archived_at timestamp(0) without time zone,
    employment_type character varying(255) DEFAULT 'hourly'::character varying NOT NULL,
    is_overtime_exempt boolean DEFAULT false NOT NULL,
    hourly_rate numeric(10,2),
    annual_salary numeric(12,2),
    CONSTRAINT users_employment_type_check CHECK (((employment_type)::text = ANY (ARRAY[('hourly'::character varying)::text, ('salary'::character varying)::text, ('contract'::character varying)::text])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vendors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendors (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    contact_name character varying(255),
    phone character varying(255),
    extension character varying(255),
    email character varying(255),
    website character varying(255),
    hours character varying(255),
    sla character varying(255),
    code character varying(255),
    account_number character varying(255),
    notes text,
    template boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone,
    accessed_at timestamp(0) without time zone,
    client_id bigint,
    template_id bigint
);


--
-- Name: vendors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendors_id_seq OWNED BY public.vendors.id;


--
-- Name: widget_data_cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.widget_data_cache (
    id bigint NOT NULL,
    company_id bigint NOT NULL,
    widget_type character varying(255) NOT NULL,
    cache_key character varying(255) NOT NULL,
    data json NOT NULL,
    metadata json,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: widget_data_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.widget_data_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: widget_data_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.widget_data_cache_id_seq OWNED BY public.widget_data_cache.id;


--
-- Name: account_holds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_holds ALTER COLUMN id SET DEFAULT nextval('public.account_holds_id_seq'::regclass);


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: analytics_snapshots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.analytics_snapshots ALTER COLUMN id SET DEFAULT nextval('public.analytics_snapshots_id_seq'::regclass);


--
-- Name: asset_depreciations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_depreciations ALTER COLUMN id SET DEFAULT nextval('public.asset_depreciations_id_seq'::regclass);


--
-- Name: asset_maintenance id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_maintenance ALTER COLUMN id SET DEFAULT nextval('public.asset_maintenance_id_seq'::regclass);


--
-- Name: asset_warranties id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_warranties ALTER COLUMN id SET DEFAULT nextval('public.asset_warranties_id_seq'::regclass);


--
-- Name: assets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets ALTER COLUMN id SET DEFAULT nextval('public.assets_id_seq'::regclass);


--
-- Name: attribution_touchpoints id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints ALTER COLUMN id SET DEFAULT nextval('public.attribution_touchpoints_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: auto_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auto_payments ALTER COLUMN id SET DEFAULT nextval('public.auto_payments_id_seq'::regclass);


--
-- Name: bouncer_abilities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_abilities ALTER COLUMN id SET DEFAULT nextval('public.bouncer_abilities_id_seq'::regclass);


--
-- Name: bouncer_assigned_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_assigned_roles ALTER COLUMN id SET DEFAULT nextval('public.bouncer_assigned_roles_id_seq'::regclass);


--
-- Name: bouncer_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_permissions ALTER COLUMN id SET DEFAULT nextval('public.bouncer_permissions_id_seq'::regclass);


--
-- Name: bouncer_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_roles ALTER COLUMN id SET DEFAULT nextval('public.bouncer_roles_id_seq'::regclass);


--
-- Name: campaign_enrollments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_enrollments ALTER COLUMN id SET DEFAULT nextval('public.campaign_enrollments_id_seq'::regclass);


--
-- Name: campaign_sequences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sequences ALTER COLUMN id SET DEFAULT nextval('public.campaign_sequences_id_seq'::regclass);


--
-- Name: cash_flow_projections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_flow_projections ALTER COLUMN id SET DEFAULT nextval('public.cash_flow_projections_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: client_addresses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_addresses ALTER COLUMN id SET DEFAULT nextval('public.client_addresses_id_seq'::regclass);


--
-- Name: client_calendar_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_calendar_events ALTER COLUMN id SET DEFAULT nextval('public.client_calendar_events_id_seq'::regclass);


--
-- Name: client_certificates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_certificates ALTER COLUMN id SET DEFAULT nextval('public.client_certificates_id_seq'::regclass);


--
-- Name: client_contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_contacts ALTER COLUMN id SET DEFAULT nextval('public.client_contacts_id_seq'::regclass);


--
-- Name: client_credentials id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credentials ALTER COLUMN id SET DEFAULT nextval('public.client_credentials_id_seq'::regclass);


--
-- Name: client_credit_applications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications ALTER COLUMN id SET DEFAULT nextval('public.client_credit_applications_id_seq'::regclass);


--
-- Name: client_credits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits ALTER COLUMN id SET DEFAULT nextval('public.client_credits_id_seq'::regclass);


--
-- Name: client_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents ALTER COLUMN id SET DEFAULT nextval('public.client_documents_id_seq'::regclass);


--
-- Name: client_domains id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_domains ALTER COLUMN id SET DEFAULT nextval('public.client_domains_id_seq'::regclass);


--
-- Name: client_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_files ALTER COLUMN id SET DEFAULT nextval('public.client_files_id_seq'::regclass);


--
-- Name: client_it_documentation id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation ALTER COLUMN id SET DEFAULT nextval('public.client_it_documentation_id_seq'::regclass);


--
-- Name: client_licenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_licenses ALTER COLUMN id SET DEFAULT nextval('public.client_licenses_id_seq'::regclass);


--
-- Name: client_networks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_networks ALTER COLUMN id SET DEFAULT nextval('public.client_networks_id_seq'::regclass);


--
-- Name: client_portal_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions ALTER COLUMN id SET DEFAULT nextval('public.client_portal_sessions_id_seq'::regclass);


--
-- Name: client_portal_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_users ALTER COLUMN id SET DEFAULT nextval('public.client_portal_users_id_seq'::regclass);


--
-- Name: client_quotes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_quotes ALTER COLUMN id SET DEFAULT nextval('public.client_quotes_id_seq'::regclass);


--
-- Name: client_racks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_racks ALTER COLUMN id SET DEFAULT nextval('public.client_racks_id_seq'::regclass);


--
-- Name: client_recurring_invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recurring_invoices ALTER COLUMN id SET DEFAULT nextval('public.client_recurring_invoices_id_seq'::regclass);


--
-- Name: client_services id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services ALTER COLUMN id SET DEFAULT nextval('public.client_services_id_seq'::regclass);


--
-- Name: client_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags ALTER COLUMN id SET DEFAULT nextval('public.client_tags_id_seq'::regclass);


--
-- Name: client_trips id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_trips ALTER COLUMN id SET DEFAULT nextval('public.client_trips_id_seq'::regclass);


--
-- Name: client_vendors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_vendors ALTER COLUMN id SET DEFAULT nextval('public.client_vendors_id_seq'::regclass);


--
-- Name: clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients ALTER COLUMN id SET DEFAULT nextval('public.clients_id_seq'::regclass);


--
-- Name: collection_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_notes ALTER COLUMN id SET DEFAULT nextval('public.collection_notes_id_seq'::regclass);


--
-- Name: communication_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs ALTER COLUMN id SET DEFAULT nextval('public.communication_logs_id_seq'::regclass);


--
-- Name: companies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies ALTER COLUMN id SET DEFAULT nextval('public.companies_id_seq'::regclass);


--
-- Name: company_customizations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_customizations ALTER COLUMN id SET DEFAULT nextval('public.company_customizations_id_seq'::regclass);


--
-- Name: company_hierarchies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_hierarchies ALTER COLUMN id SET DEFAULT nextval('public.company_hierarchies_id_seq'::regclass);


--
-- Name: company_mail_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_mail_settings ALTER COLUMN id SET DEFAULT nextval('public.company_mail_settings_id_seq'::regclass);


--
-- Name: company_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.company_subscriptions_id_seq'::regclass);


--
-- Name: compliance_checks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_checks ALTER COLUMN id SET DEFAULT nextval('public.compliance_checks_id_seq'::regclass);


--
-- Name: compliance_requirements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_requirements ALTER COLUMN id SET DEFAULT nextval('public.compliance_requirements_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: contract_action_buttons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_action_buttons ALTER COLUMN id SET DEFAULT nextval('public.contract_action_buttons_id_seq'::regclass);


--
-- Name: contract_amendments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments ALTER COLUMN id SET DEFAULT nextval('public.contract_amendments_id_seq'::regclass);


--
-- Name: contract_approvals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals ALTER COLUMN id SET DEFAULT nextval('public.contract_approvals_id_seq'::regclass);


--
-- Name: contract_asset_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments ALTER COLUMN id SET DEFAULT nextval('public.contract_asset_assignments_id_seq'::regclass);


--
-- Name: contract_billing_calculations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations ALTER COLUMN id SET DEFAULT nextval('public.contract_billing_calculations_id_seq'::regclass);


--
-- Name: contract_billing_model_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_model_definitions ALTER COLUMN id SET DEFAULT nextval('public.contract_billing_model_definitions_id_seq'::regclass);


--
-- Name: contract_clauses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses ALTER COLUMN id SET DEFAULT nextval('public.contract_clauses_id_seq'::regclass);


--
-- Name: contract_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments ALTER COLUMN id SET DEFAULT nextval('public.contract_comments_id_seq'::regclass);


--
-- Name: contract_component_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments ALTER COLUMN id SET DEFAULT nextval('public.contract_component_assignments_id_seq'::regclass);


--
-- Name: contract_components id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_components ALTER COLUMN id SET DEFAULT nextval('public.contract_components_id_seq'::regclass);


--
-- Name: contract_configurations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations ALTER COLUMN id SET DEFAULT nextval('public.contract_configurations_id_seq'::regclass);


--
-- Name: contract_contact_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments ALTER COLUMN id SET DEFAULT nextval('public.contract_contact_assignments_id_seq'::regclass);


--
-- Name: contract_dashboard_widgets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_dashboard_widgets ALTER COLUMN id SET DEFAULT nextval('public.contract_dashboard_widgets_id_seq'::regclass);


--
-- Name: contract_detail_configurations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_detail_configurations ALTER COLUMN id SET DEFAULT nextval('public.contract_detail_configurations_id_seq'::regclass);


--
-- Name: contract_field_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_field_definitions ALTER COLUMN id SET DEFAULT nextval('public.contract_field_definitions_id_seq'::regclass);


--
-- Name: contract_form_sections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_form_sections ALTER COLUMN id SET DEFAULT nextval('public.contract_form_sections_id_seq'::regclass);


--
-- Name: contract_invoice id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice ALTER COLUMN id SET DEFAULT nextval('public.contract_invoice_id_seq'::regclass);


--
-- Name: contract_list_configurations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_list_configurations ALTER COLUMN id SET DEFAULT nextval('public.contract_list_configurations_id_seq'::regclass);


--
-- Name: contract_menu_sections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_menu_sections ALTER COLUMN id SET DEFAULT nextval('public.contract_menu_sections_id_seq'::regclass);


--
-- Name: contract_milestones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones ALTER COLUMN id SET DEFAULT nextval('public.contract_milestones_id_seq'::regclass);


--
-- Name: contract_navigation_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_navigation_items ALTER COLUMN id SET DEFAULT nextval('public.contract_navigation_items_id_seq'::regclass);


--
-- Name: contract_negotiations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations ALTER COLUMN id SET DEFAULT nextval('public.contract_negotiations_id_seq'::regclass);


--
-- Name: contract_schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules ALTER COLUMN id SET DEFAULT nextval('public.contract_schedules_id_seq'::regclass);


--
-- Name: contract_signatures id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_signatures ALTER COLUMN id SET DEFAULT nextval('public.contract_signatures_id_seq'::regclass);


--
-- Name: contract_status_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_definitions ALTER COLUMN id SET DEFAULT nextval('public.contract_status_definitions_id_seq'::regclass);


--
-- Name: contract_status_transitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_transitions ALTER COLUMN id SET DEFAULT nextval('public.contract_status_transitions_id_seq'::regclass);


--
-- Name: contract_template_clauses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_template_clauses ALTER COLUMN id SET DEFAULT nextval('public.contract_template_clauses_id_seq'::regclass);


--
-- Name: contract_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates ALTER COLUMN id SET DEFAULT nextval('public.contract_templates_id_seq'::regclass);


--
-- Name: contract_type_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_definitions ALTER COLUMN id SET DEFAULT nextval('public.contract_type_definitions_id_seq'::regclass);


--
-- Name: contract_type_form_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_form_mappings ALTER COLUMN id SET DEFAULT nextval('public.contract_type_form_mappings_id_seq'::regclass);


--
-- Name: contract_versions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions ALTER COLUMN id SET DEFAULT nextval('public.contract_versions_id_seq'::regclass);


--
-- Name: contract_view_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_view_definitions ALTER COLUMN id SET DEFAULT nextval('public.contract_view_definitions_id_seq'::regclass);


--
-- Name: contracts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts ALTER COLUMN id SET DEFAULT nextval('public.contracts_id_seq'::regclass);


--
-- Name: conversion_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events ALTER COLUMN id SET DEFAULT nextval('public.conversion_events_id_seq'::regclass);


--
-- Name: credit_applications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_applications ALTER COLUMN id SET DEFAULT nextval('public.credit_applications_id_seq'::regclass);


--
-- Name: credit_note_approvals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_approvals ALTER COLUMN id SET DEFAULT nextval('public.credit_note_approvals_id_seq'::regclass);


--
-- Name: credit_note_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_items ALTER COLUMN id SET DEFAULT nextval('public.credit_note_items_id_seq'::regclass);


--
-- Name: credit_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes ALTER COLUMN id SET DEFAULT nextval('public.credit_notes_id_seq'::regclass);


--
-- Name: cross_company_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users ALTER COLUMN id SET DEFAULT nextval('public.cross_company_users_id_seq'::regclass);


--
-- Name: custom_quick_actions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_quick_actions ALTER COLUMN id SET DEFAULT nextval('public.custom_quick_actions_id_seq'::regclass);


--
-- Name: dashboard_activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_activity_logs ALTER COLUMN id SET DEFAULT nextval('public.dashboard_activity_logs_id_seq'::regclass);


--
-- Name: dashboard_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_metrics ALTER COLUMN id SET DEFAULT nextval('public.dashboard_metrics_id_seq'::regclass);


--
-- Name: dashboard_presets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_presets ALTER COLUMN id SET DEFAULT nextval('public.dashboard_presets_id_seq'::regclass);


--
-- Name: dashboard_widgets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_widgets ALTER COLUMN id SET DEFAULT nextval('public.dashboard_widgets_id_seq'::regclass);


--
-- Name: device_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings ALTER COLUMN id SET DEFAULT nextval('public.device_mappings_id_seq'::regclass);


--
-- Name: documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents ALTER COLUMN id SET DEFAULT nextval('public.documents_id_seq'::regclass);


--
-- Name: dunning_actions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions ALTER COLUMN id SET DEFAULT nextval('public.dunning_actions_id_seq'::regclass);


--
-- Name: dunning_campaigns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_campaigns ALTER COLUMN id SET DEFAULT nextval('public.dunning_campaigns_id_seq'::regclass);


--
-- Name: dunning_sequences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_sequences ALTER COLUMN id SET DEFAULT nextval('public.dunning_sequences_id_seq'::regclass);


--
-- Name: email_accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_accounts ALTER COLUMN id SET DEFAULT nextval('public.email_accounts_id_seq'::regclass);


--
-- Name: email_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments ALTER COLUMN id SET DEFAULT nextval('public.email_attachments_id_seq'::regclass);


--
-- Name: email_folders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_folders ALTER COLUMN id SET DEFAULT nextval('public.email_folders_id_seq'::regclass);


--
-- Name: email_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages ALTER COLUMN id SET DEFAULT nextval('public.email_messages_id_seq'::regclass);


--
-- Name: email_signatures id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_signatures ALTER COLUMN id SET DEFAULT nextval('public.email_signatures_id_seq'::regclass);


--
-- Name: email_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates ALTER COLUMN id SET DEFAULT nextval('public.email_templates_id_seq'::regclass);


--
-- Name: email_tracking id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking ALTER COLUMN id SET DEFAULT nextval('public.email_tracking_id_seq'::regclass);


--
-- Name: employee_schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules ALTER COLUMN id SET DEFAULT nextval('public.employee_schedules_id_seq'::regclass);


--
-- Name: employee_time_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries ALTER COLUMN id SET DEFAULT nextval('public.employee_time_entries_id_seq'::regclass);


--
-- Name: expenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses ALTER COLUMN id SET DEFAULT nextval('public.expenses_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files ALTER COLUMN id SET DEFAULT nextval('public.files_id_seq'::regclass);


--
-- Name: financial_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.financial_reports ALTER COLUMN id SET DEFAULT nextval('public.financial_reports_id_seq'::regclass);


--
-- Name: hr_settings_overrides id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_settings_overrides ALTER COLUMN id SET DEFAULT nextval('public.hr_settings_overrides_id_seq'::regclass);


--
-- Name: in_app_notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.in_app_notifications ALTER COLUMN id SET DEFAULT nextval('public.in_app_notifications_id_seq'::regclass);


--
-- Name: invoice_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items ALTER COLUMN id SET DEFAULT nextval('public.invoice_items_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: ip_lookup_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_lookup_logs ALTER COLUMN id SET DEFAULT nextval('public.ip_lookup_logs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: kpi_calculations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_calculations ALTER COLUMN id SET DEFAULT nextval('public.kpi_calculations_id_seq'::regclass);


--
-- Name: kpi_targets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_targets ALTER COLUMN id SET DEFAULT nextval('public.kpi_targets_id_seq'::regclass);


--
-- Name: lead_activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities ALTER COLUMN id SET DEFAULT nextval('public.lead_activities_id_seq'::regclass);


--
-- Name: lead_sources id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_sources ALTER COLUMN id SET DEFAULT nextval('public.lead_sources_id_seq'::regclass);


--
-- Name: leads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads ALTER COLUMN id SET DEFAULT nextval('public.leads_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: mail_queue id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue ALTER COLUMN id SET DEFAULT nextval('public.mail_queue_id_seq'::regclass);


--
-- Name: mail_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates ALTER COLUMN id SET DEFAULT nextval('public.mail_templates_id_seq'::regclass);


--
-- Name: marketing_campaigns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaigns ALTER COLUMN id SET DEFAULT nextval('public.marketing_campaigns_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: networks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.networks ALTER COLUMN id SET DEFAULT nextval('public.networks_id_seq'::regclass);


--
-- Name: notification_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_logs ALTER COLUMN id SET DEFAULT nextval('public.notification_logs_id_seq'::regclass);


--
-- Name: notification_preferences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences ALTER COLUMN id SET DEFAULT nextval('public.notification_preferences_id_seq'::regclass);


--
-- Name: oauth_states id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_states ALTER COLUMN id SET DEFAULT nextval('public.oauth_states_id_seq'::regclass);


--
-- Name: pay_periods id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pay_periods ALTER COLUMN id SET DEFAULT nextval('public.pay_periods_id_seq'::regclass);


--
-- Name: payment_applications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications ALTER COLUMN id SET DEFAULT nextval('public.payment_applications_id_seq'::regclass);


--
-- Name: payment_methods id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods ALTER COLUMN id SET DEFAULT nextval('public.payment_methods_id_seq'::regclass);


--
-- Name: payment_plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_plans ALTER COLUMN id SET DEFAULT nextval('public.payment_plans_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: permission_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_groups ALTER COLUMN id SET DEFAULT nextval('public.permission_groups_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: physical_mail_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_settings ALTER COLUMN id SET DEFAULT nextval('public.physical_mail_settings_id_seq'::regclass);


--
-- Name: portal_notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal_notifications ALTER COLUMN id SET DEFAULT nextval('public.portal_notifications_id_seq'::regclass);


--
-- Name: pricing_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing_rules ALTER COLUMN id SET DEFAULT nextval('public.pricing_rules_id_seq'::regclass);


--
-- Name: product_bundle_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundle_items ALTER COLUMN id SET DEFAULT nextval('public.product_bundle_items_id_seq'::regclass);


--
-- Name: product_bundles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundles ALTER COLUMN id SET DEFAULT nextval('public.product_bundles_id_seq'::regclass);


--
-- Name: product_tax_data id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tax_data ALTER COLUMN id SET DEFAULT nextval('public.product_tax_data_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: project_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_comments ALTER COLUMN id SET DEFAULT nextval('public.project_comments_id_seq'::regclass);


--
-- Name: project_expenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_expenses ALTER COLUMN id SET DEFAULT nextval('public.project_expenses_id_seq'::regclass);


--
-- Name: project_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_files ALTER COLUMN id SET DEFAULT nextval('public.project_files_id_seq'::regclass);


--
-- Name: project_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members ALTER COLUMN id SET DEFAULT nextval('public.project_members_id_seq'::regclass);


--
-- Name: project_milestones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones ALTER COLUMN id SET DEFAULT nextval('public.project_milestones_id_seq'::regclass);


--
-- Name: project_tasks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks ALTER COLUMN id SET DEFAULT nextval('public.project_tasks_id_seq'::regclass);


--
-- Name: project_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_templates ALTER COLUMN id SET DEFAULT nextval('public.project_templates_id_seq'::regclass);


--
-- Name: project_time_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_time_entries ALTER COLUMN id SET DEFAULT nextval('public.project_time_entries_id_seq'::regclass);


--
-- Name: projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);


--
-- Name: push_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.push_subscriptions_id_seq'::regclass);


--
-- Name: quick_action_favorites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites ALTER COLUMN id SET DEFAULT nextval('public.quick_action_favorites_id_seq'::regclass);


--
-- Name: quote_approvals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_approvals ALTER COLUMN id SET DEFAULT nextval('public.quote_approvals_id_seq'::regclass);


--
-- Name: quote_invoice_conversions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_invoice_conversions ALTER COLUMN id SET DEFAULT nextval('public.quote_invoice_conversions_id_seq'::regclass);


--
-- Name: quote_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_templates ALTER COLUMN id SET DEFAULT nextval('public.quote_templates_id_seq'::regclass);


--
-- Name: quote_versions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions ALTER COLUMN id SET DEFAULT nextval('public.quote_versions_id_seq'::regclass);


--
-- Name: quotes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes ALTER COLUMN id SET DEFAULT nextval('public.quotes_id_seq'::regclass);


--
-- Name: rate_cards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rate_cards ALTER COLUMN id SET DEFAULT nextval('public.rate_cards_id_seq'::regclass);


--
-- Name: recurring id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring ALTER COLUMN id SET DEFAULT nextval('public.recurring_id_seq'::regclass);


--
-- Name: recurring_invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_invoices ALTER COLUMN id SET DEFAULT nextval('public.recurring_invoices_id_seq'::regclass);


--
-- Name: recurring_tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_tickets ALTER COLUMN id SET DEFAULT nextval('public.recurring_tickets_id_seq'::regclass);


--
-- Name: refund_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests ALTER COLUMN id SET DEFAULT nextval('public.refund_requests_id_seq'::regclass);


--
-- Name: refund_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_transactions ALTER COLUMN id SET DEFAULT nextval('public.refund_transactions_id_seq'::regclass);


--
-- Name: report_exports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_exports ALTER COLUMN id SET DEFAULT nextval('public.report_exports_id_seq'::regclass);


--
-- Name: report_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_metrics ALTER COLUMN id SET DEFAULT nextval('public.report_metrics_id_seq'::regclass);


--
-- Name: report_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.report_subscriptions_id_seq'::regclass);


--
-- Name: report_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates ALTER COLUMN id SET DEFAULT nextval('public.report_templates_id_seq'::regclass);


--
-- Name: revenue_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revenue_metrics ALTER COLUMN id SET DEFAULT nextval('public.revenue_metrics_id_seq'::regclass);


--
-- Name: rmm_client_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings ALTER COLUMN id SET DEFAULT nextval('public.rmm_client_mappings_id_seq'::regclass);


--
-- Name: rmm_integrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_integrations ALTER COLUMN id SET DEFAULT nextval('public.rmm_integrations_id_seq'::regclass);


--
-- Name: saved_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_reports ALTER COLUMN id SET DEFAULT nextval('public.saved_reports_id_seq'::regclass);


--
-- Name: scheduler_coordination id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduler_coordination ALTER COLUMN id SET DEFAULT nextval('public.scheduler_coordination_id_seq'::regclass);


--
-- Name: service_tax_rates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_tax_rates ALTER COLUMN id SET DEFAULT nextval('public.service_tax_rates_id_seq'::regclass);


--
-- Name: services id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services ALTER COLUMN id SET DEFAULT nextval('public.services_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: settings_configurations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings_configurations ALTER COLUMN id SET DEFAULT nextval('public.settings_configurations_id_seq'::regclass);


--
-- Name: shifts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shifts ALTER COLUMN id SET DEFAULT nextval('public.shifts_id_seq'::regclass);


--
-- Name: slas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slas ALTER COLUMN id SET DEFAULT nextval('public.slas_id_seq'::regclass);


--
-- Name: subscription_plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans ALTER COLUMN id SET DEFAULT nextval('public.subscription_plans_id_seq'::regclass);


--
-- Name: subsidiary_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions ALTER COLUMN id SET DEFAULT nextval('public.subsidiary_permissions_id_seq'::regclass);


--
-- Name: suspicious_login_attempts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suspicious_login_attempts ALTER COLUMN id SET DEFAULT nextval('public.suspicious_login_attempts_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: task_checklist_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_checklist_items ALTER COLUMN id SET DEFAULT nextval('public.task_checklist_items_id_seq'::regclass);


--
-- Name: task_dependencies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies ALTER COLUMN id SET DEFAULT nextval('public.task_dependencies_id_seq'::regclass);


--
-- Name: task_watchers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers ALTER COLUMN id SET DEFAULT nextval('public.task_watchers_id_seq'::regclass);


--
-- Name: tax_api_query_cache id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_query_cache ALTER COLUMN id SET DEFAULT nextval('public.tax_api_query_cache_id_seq'::regclass);


--
-- Name: tax_api_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_settings ALTER COLUMN id SET DEFAULT nextval('public.tax_api_settings_id_seq'::regclass);


--
-- Name: tax_calculations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations ALTER COLUMN id SET DEFAULT nextval('public.tax_calculations_id_seq'::regclass);


--
-- Name: tax_exemptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_exemptions ALTER COLUMN id SET DEFAULT nextval('public.tax_exemptions_id_seq'::regclass);


--
-- Name: tax_jurisdictions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_jurisdictions ALTER COLUMN id SET DEFAULT nextval('public.tax_jurisdictions_id_seq'::regclass);


--
-- Name: tax_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_profiles ALTER COLUMN id SET DEFAULT nextval('public.tax_profiles_id_seq'::regclass);


--
-- Name: taxes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes ALTER COLUMN id SET DEFAULT nextval('public.taxes_id_seq'::regclass);


--
-- Name: ticket_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments ALTER COLUMN id SET DEFAULT nextval('public.ticket_assignments_id_seq'::regclass);


--
-- Name: ticket_calendar_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_calendar_events ALTER COLUMN id SET DEFAULT nextval('public.ticket_calendar_events_id_seq'::regclass);


--
-- Name: ticket_comment_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comment_attachments ALTER COLUMN id SET DEFAULT nextval('public.ticket_comment_attachments_id_seq'::regclass);


--
-- Name: ticket_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments ALTER COLUMN id SET DEFAULT nextval('public.ticket_comments_id_seq'::regclass);


--
-- Name: ticket_priority_queue id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queue ALTER COLUMN id SET DEFAULT nextval('public.ticket_priority_queue_id_seq'::regclass);


--
-- Name: ticket_priority_queues id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queues ALTER COLUMN id SET DEFAULT nextval('public.ticket_priority_queues_id_seq'::regclass);


--
-- Name: ticket_ratings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings ALTER COLUMN id SET DEFAULT nextval('public.ticket_ratings_id_seq'::regclass);


--
-- Name: ticket_replies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_replies ALTER COLUMN id SET DEFAULT nextval('public.ticket_replies_id_seq'::regclass);


--
-- Name: ticket_status_transitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_status_transitions ALTER COLUMN id SET DEFAULT nextval('public.ticket_status_transitions_id_seq'::regclass);


--
-- Name: ticket_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_templates ALTER COLUMN id SET DEFAULT nextval('public.ticket_templates_id_seq'::regclass);


--
-- Name: ticket_time_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_time_entries ALTER COLUMN id SET DEFAULT nextval('public.ticket_time_entries_id_seq'::regclass);


--
-- Name: ticket_watchers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers ALTER COLUMN id SET DEFAULT nextval('public.ticket_watchers_id_seq'::regclass);


--
-- Name: ticket_workflows id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_workflows ALTER COLUMN id SET DEFAULT nextval('public.ticket_workflows_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.tickets_id_seq'::regclass);


--
-- Name: time_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries ALTER COLUMN id SET DEFAULT nextval('public.time_entries_id_seq'::regclass);


--
-- Name: time_entry_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_templates ALTER COLUMN id SET DEFAULT nextval('public.time_entry_templates_id_seq'::regclass);


--
-- Name: time_off_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_off_requests ALTER COLUMN id SET DEFAULT nextval('public.time_off_requests_id_seq'::regclass);


--
-- Name: trusted_devices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trusted_devices ALTER COLUMN id SET DEFAULT nextval('public.trusted_devices_id_seq'::regclass);


--
-- Name: usage_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_alerts ALTER COLUMN id SET DEFAULT nextval('public.usage_alerts_id_seq'::regclass);


--
-- Name: usage_buckets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_buckets ALTER COLUMN id SET DEFAULT nextval('public.usage_buckets_id_seq'::regclass);


--
-- Name: usage_pools id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_pools ALTER COLUMN id SET DEFAULT nextval('public.usage_pools_id_seq'::regclass);


--
-- Name: usage_records id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_records ALTER COLUMN id SET DEFAULT nextval('public.usage_records_id_seq'::regclass);


--
-- Name: usage_tiers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_tiers ALTER COLUMN id SET DEFAULT nextval('public.usage_tiers_id_seq'::regclass);


--
-- Name: user_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_clients ALTER COLUMN id SET DEFAULT nextval('public.user_clients_id_seq'::regclass);


--
-- Name: user_dashboard_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_dashboard_configs ALTER COLUMN id SET DEFAULT nextval('public.user_dashboard_configs_id_seq'::regclass);


--
-- Name: user_favorite_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_favorite_clients ALTER COLUMN id SET DEFAULT nextval('public.user_favorite_clients_id_seq'::regclass);


--
-- Name: user_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles ALTER COLUMN id SET DEFAULT nextval('public.user_roles_id_seq'::regclass);


--
-- Name: user_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings ALTER COLUMN id SET DEFAULT nextval('public.user_settings_id_seq'::regclass);


--
-- Name: user_widget_instances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_widget_instances ALTER COLUMN id SET DEFAULT nextval('public.user_widget_instances_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vendors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors ALTER COLUMN id SET DEFAULT nextval('public.vendors_id_seq'::regclass);


--
-- Name: widget_data_cache id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_data_cache ALTER COLUMN id SET DEFAULT nextval('public.widget_data_cache_id_seq'::regclass);


--
-- Name: account_holds account_holds_hold_reference_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_holds
    ADD CONSTRAINT account_holds_hold_reference_unique UNIQUE (hold_reference);


--
-- Name: account_holds account_holds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_holds
    ADD CONSTRAINT account_holds_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: analytics_snapshots analytics_snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.analytics_snapshots
    ADD CONSTRAINT analytics_snapshots_pkey PRIMARY KEY (id);


--
-- Name: asset_depreciations asset_depreciations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_depreciations
    ADD CONSTRAINT asset_depreciations_pkey PRIMARY KEY (id);


--
-- Name: asset_maintenance asset_maintenance_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_maintenance
    ADD CONSTRAINT asset_maintenance_pkey PRIMARY KEY (id);


--
-- Name: asset_warranties asset_warranties_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_warranties
    ADD CONSTRAINT asset_warranties_pkey PRIMARY KEY (id);


--
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (id);


--
-- Name: attribution_touchpoints attribution_touchpoints_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: auto_payments auto_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auto_payments
    ADD CONSTRAINT auto_payments_pkey PRIMARY KEY (id);


--
-- Name: bouncer_abilities bouncer_abilities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_abilities
    ADD CONSTRAINT bouncer_abilities_pkey PRIMARY KEY (id);


--
-- Name: bouncer_assigned_roles bouncer_assigned_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_assigned_roles
    ADD CONSTRAINT bouncer_assigned_roles_pkey PRIMARY KEY (id);


--
-- Name: bouncer_permissions bouncer_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_permissions
    ADD CONSTRAINT bouncer_permissions_pkey PRIMARY KEY (id);


--
-- Name: bouncer_roles bouncer_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_roles
    ADD CONSTRAINT bouncer_roles_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: campaign_enrollments campaign_enrollments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_enrollments
    ADD CONSTRAINT campaign_enrollments_pkey PRIMARY KEY (id);


--
-- Name: campaign_sequences campaign_sequences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sequences
    ADD CONSTRAINT campaign_sequences_pkey PRIMARY KEY (id);


--
-- Name: cash_flow_projections cash_flow_projections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_flow_projections
    ADD CONSTRAINT cash_flow_projections_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: client_addresses client_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_addresses
    ADD CONSTRAINT client_addresses_pkey PRIMARY KEY (id);


--
-- Name: client_calendar_events client_calendar_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_calendar_events
    ADD CONSTRAINT client_calendar_events_pkey PRIMARY KEY (id);


--
-- Name: client_certificates client_certificates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_certificates
    ADD CONSTRAINT client_certificates_pkey PRIMARY KEY (id);


--
-- Name: client_contacts client_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_contacts
    ADD CONSTRAINT client_contacts_pkey PRIMARY KEY (id);


--
-- Name: client_credentials client_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credentials
    ADD CONSTRAINT client_credentials_pkey PRIMARY KEY (id);


--
-- Name: client_credit_applications client_credit_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications
    ADD CONSTRAINT client_credit_applications_pkey PRIMARY KEY (id);


--
-- Name: client_credits client_credits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits
    ADD CONSTRAINT client_credits_pkey PRIMARY KEY (id);


--
-- Name: client_credits client_credits_reference_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits
    ADD CONSTRAINT client_credits_reference_number_unique UNIQUE (reference_number);


--
-- Name: client_documents client_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_pkey PRIMARY KEY (id);


--
-- Name: client_domains client_domains_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_domains
    ADD CONSTRAINT client_domains_pkey PRIMARY KEY (id);


--
-- Name: client_files client_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_files
    ADD CONSTRAINT client_files_pkey PRIMARY KEY (id);


--
-- Name: client_it_documentation client_it_documentation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation
    ADD CONSTRAINT client_it_documentation_pkey PRIMARY KEY (id);


--
-- Name: client_licenses client_licenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_licenses
    ADD CONSTRAINT client_licenses_pkey PRIMARY KEY (id);


--
-- Name: client_networks client_networks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_networks
    ADD CONSTRAINT client_networks_pkey PRIMARY KEY (id);


--
-- Name: client_portal_sessions client_portal_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions
    ADD CONSTRAINT client_portal_sessions_pkey PRIMARY KEY (id);


--
-- Name: client_portal_sessions client_portal_sessions_refresh_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions
    ADD CONSTRAINT client_portal_sessions_refresh_token_unique UNIQUE (refresh_token);


--
-- Name: client_portal_sessions client_portal_sessions_session_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions
    ADD CONSTRAINT client_portal_sessions_session_token_unique UNIQUE (session_token);


--
-- Name: client_portal_users client_portal_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_users
    ADD CONSTRAINT client_portal_users_pkey PRIMARY KEY (id);


--
-- Name: client_quotes client_quotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_quotes
    ADD CONSTRAINT client_quotes_pkey PRIMARY KEY (id);


--
-- Name: client_racks client_racks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_racks
    ADD CONSTRAINT client_racks_pkey PRIMARY KEY (id);


--
-- Name: client_recurring_invoices client_recurring_invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recurring_invoices
    ADD CONSTRAINT client_recurring_invoices_pkey PRIMARY KEY (id);


--
-- Name: client_services client_services_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_pkey PRIMARY KEY (id);


--
-- Name: client_tags client_tags_client_id_tag_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags
    ADD CONSTRAINT client_tags_client_id_tag_id_unique UNIQUE (client_id, tag_id);


--
-- Name: client_tags client_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags
    ADD CONSTRAINT client_tags_pkey PRIMARY KEY (id);


--
-- Name: client_trips client_trips_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_trips
    ADD CONSTRAINT client_trips_pkey PRIMARY KEY (id);


--
-- Name: client_vendors client_vendors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_vendors
    ADD CONSTRAINT client_vendors_pkey PRIMARY KEY (id);


--
-- Name: clients clients_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_email_unique UNIQUE (email);


--
-- Name: clients clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);


--
-- Name: collection_notes collection_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_notes
    ADD CONSTRAINT collection_notes_pkey PRIMARY KEY (id);


--
-- Name: communication_logs communication_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs
    ADD CONSTRAINT communication_logs_pkey PRIMARY KEY (id);


--
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- Name: company_customizations company_customizations_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_customizations
    ADD CONSTRAINT company_customizations_company_id_unique UNIQUE (company_id);


--
-- Name: company_customizations company_customizations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_customizations
    ADD CONSTRAINT company_customizations_pkey PRIMARY KEY (id);


--
-- Name: company_hierarchies company_hierarchies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_hierarchies
    ADD CONSTRAINT company_hierarchies_pkey PRIMARY KEY (id);


--
-- Name: company_mail_settings company_mail_settings_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_mail_settings
    ADD CONSTRAINT company_mail_settings_company_id_unique UNIQUE (company_id);


--
-- Name: company_mail_settings company_mail_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_mail_settings
    ADD CONSTRAINT company_mail_settings_pkey PRIMARY KEY (id);


--
-- Name: company_subscriptions company_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_subscriptions
    ADD CONSTRAINT company_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: company_subscriptions company_subscriptions_stripe_subscription_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_subscriptions
    ADD CONSTRAINT company_subscriptions_stripe_subscription_id_unique UNIQUE (stripe_subscription_id);


--
-- Name: compliance_checks compliance_checks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_checks
    ADD CONSTRAINT compliance_checks_pkey PRIMARY KEY (id);


--
-- Name: compliance_requirements compliance_requirements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_requirements
    ADD CONSTRAINT compliance_requirements_pkey PRIMARY KEY (id);


--
-- Name: contacts contacts_invitation_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_invitation_token_unique UNIQUE (invitation_token);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: contract_action_buttons contract_action_buttons_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_action_buttons
    ADD CONSTRAINT contract_action_buttons_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_action_buttons contract_action_buttons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_action_buttons
    ADD CONSTRAINT contract_action_buttons_pkey PRIMARY KEY (id);


--
-- Name: contract_amendments contract_amendments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments
    ADD CONSTRAINT contract_amendments_pkey PRIMARY KEY (id);


--
-- Name: contract_approvals contract_approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_pkey PRIMARY KEY (id);


--
-- Name: contract_asset_assignments contract_asset_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_pkey PRIMARY KEY (id);


--
-- Name: contract_billing_calculations contract_billing_calculations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_pkey PRIMARY KEY (id);


--
-- Name: contract_billing_model_definitions contract_billing_model_definitions_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_model_definitions
    ADD CONSTRAINT contract_billing_model_definitions_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_billing_model_definitions contract_billing_model_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_model_definitions
    ADD CONSTRAINT contract_billing_model_definitions_pkey PRIMARY KEY (id);


--
-- Name: contract_clauses contract_clauses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses
    ADD CONSTRAINT contract_clauses_pkey PRIMARY KEY (id);


--
-- Name: contract_clauses contract_clauses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses
    ADD CONSTRAINT contract_clauses_slug_unique UNIQUE (slug);


--
-- Name: contract_comments contract_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_pkey PRIMARY KEY (id);


--
-- Name: contract_component_assignments contract_component_assignments_contract_id_component_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments
    ADD CONSTRAINT contract_component_assignments_contract_id_component_id_unique UNIQUE (contract_id, component_id);


--
-- Name: contract_component_assignments contract_component_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments
    ADD CONSTRAINT contract_component_assignments_pkey PRIMARY KEY (id);


--
-- Name: contract_components contract_components_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_components
    ADD CONSTRAINT contract_components_pkey PRIMARY KEY (id);


--
-- Name: contract_configurations contract_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations
    ADD CONSTRAINT contract_configurations_pkey PRIMARY KEY (id);


--
-- Name: contract_contact_assignments contract_contact_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_pkey PRIMARY KEY (id);


--
-- Name: contract_dashboard_widgets contract_dashboard_widgets_company_id_widget_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_dashboard_widgets
    ADD CONSTRAINT contract_dashboard_widgets_company_id_widget_slug_unique UNIQUE (company_id, widget_slug);


--
-- Name: contract_dashboard_widgets contract_dashboard_widgets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_dashboard_widgets
    ADD CONSTRAINT contract_dashboard_widgets_pkey PRIMARY KEY (id);


--
-- Name: contract_detail_configurations contract_detail_configurations_company_id_contract_type_slug_un; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_detail_configurations
    ADD CONSTRAINT contract_detail_configurations_company_id_contract_type_slug_un UNIQUE (company_id, contract_type_slug);


--
-- Name: contract_detail_configurations contract_detail_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_detail_configurations
    ADD CONSTRAINT contract_detail_configurations_pkey PRIMARY KEY (id);


--
-- Name: contract_field_definitions contract_field_definitions_company_id_field_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_field_definitions
    ADD CONSTRAINT contract_field_definitions_company_id_field_slug_unique UNIQUE (company_id, field_slug);


--
-- Name: contract_field_definitions contract_field_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_field_definitions
    ADD CONSTRAINT contract_field_definitions_pkey PRIMARY KEY (id);


--
-- Name: contract_form_sections contract_form_sections_company_id_section_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_form_sections
    ADD CONSTRAINT contract_form_sections_company_id_section_slug_unique UNIQUE (company_id, section_slug);


--
-- Name: contract_form_sections contract_form_sections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_form_sections
    ADD CONSTRAINT contract_form_sections_pkey PRIMARY KEY (id);


--
-- Name: contract_invoice contract_invoice_contract_id_invoice_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_contract_id_invoice_id_unique UNIQUE (contract_id, invoice_id);


--
-- Name: contract_invoice contract_invoice_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_pkey PRIMARY KEY (id);


--
-- Name: contract_list_configurations contract_list_config_company_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_list_configurations
    ADD CONSTRAINT contract_list_config_company_type_unique UNIQUE (company_id, contract_type_slug);


--
-- Name: contract_list_configurations contract_list_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_list_configurations
    ADD CONSTRAINT contract_list_configurations_pkey PRIMARY KEY (id);


--
-- Name: contract_menu_sections contract_menu_sections_company_id_section_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_menu_sections
    ADD CONSTRAINT contract_menu_sections_company_id_section_slug_unique UNIQUE (company_id, section_slug);


--
-- Name: contract_menu_sections contract_menu_sections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_menu_sections
    ADD CONSTRAINT contract_menu_sections_pkey PRIMARY KEY (id);


--
-- Name: contract_milestones contract_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_pkey PRIMARY KEY (id);


--
-- Name: contract_navigation_items contract_navigation_items_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_navigation_items
    ADD CONSTRAINT contract_navigation_items_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_navigation_items contract_navigation_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_navigation_items
    ADD CONSTRAINT contract_navigation_items_pkey PRIMARY KEY (id);


--
-- Name: contract_negotiations contract_negotiations_negotiation_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_negotiation_number_unique UNIQUE (negotiation_number);


--
-- Name: contract_negotiations contract_negotiations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_pkey PRIMARY KEY (id);


--
-- Name: contract_schedules contract_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_pkey PRIMARY KEY (id);


--
-- Name: contract_signatures contract_signatures_contract_id_signer_email_signer_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_signatures
    ADD CONSTRAINT contract_signatures_contract_id_signer_email_signer_type_unique UNIQUE (contract_id, signer_email, signer_type);


--
-- Name: contract_signatures contract_signatures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_signatures
    ADD CONSTRAINT contract_signatures_pkey PRIMARY KEY (id);


--
-- Name: contract_status_definitions contract_status_definitions_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_definitions
    ADD CONSTRAINT contract_status_definitions_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_status_definitions contract_status_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_definitions
    ADD CONSTRAINT contract_status_definitions_pkey PRIMARY KEY (id);


--
-- Name: contract_status_transitions contract_status_transitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_transitions
    ADD CONSTRAINT contract_status_transitions_pkey PRIMARY KEY (id);


--
-- Name: contract_template_clauses contract_template_clauses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_template_clauses
    ADD CONSTRAINT contract_template_clauses_pkey PRIMARY KEY (id);


--
-- Name: contract_template_clauses contract_template_clauses_template_id_clause_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_template_clauses
    ADD CONSTRAINT contract_template_clauses_template_id_clause_id_unique UNIQUE (template_id, clause_id);


--
-- Name: contract_templates contract_templates_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_templates contract_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_pkey PRIMARY KEY (id);


--
-- Name: contract_type_definitions contract_type_definitions_company_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_definitions
    ADD CONSTRAINT contract_type_definitions_company_id_slug_unique UNIQUE (company_id, slug);


--
-- Name: contract_type_definitions contract_type_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_definitions
    ADD CONSTRAINT contract_type_definitions_pkey PRIMARY KEY (id);


--
-- Name: contract_type_form_mappings contract_type_form_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_form_mappings
    ADD CONSTRAINT contract_type_form_mappings_pkey PRIMARY KEY (id);


--
-- Name: contract_versions contract_versions_contract_id_version_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions
    ADD CONSTRAINT contract_versions_contract_id_version_number_unique UNIQUE (contract_id, version_number);


--
-- Name: contract_versions contract_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions
    ADD CONSTRAINT contract_versions_pkey PRIMARY KEY (id);


--
-- Name: contract_view_definitions contract_view_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_view_definitions
    ADD CONSTRAINT contract_view_definitions_pkey PRIMARY KEY (id);


--
-- Name: contracts contracts_company_id_contract_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_company_id_contract_number_unique UNIQUE (company_id, contract_number);


--
-- Name: contracts contracts_contract_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_contract_number_unique UNIQUE (contract_number);


--
-- Name: contracts contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_pkey PRIMARY KEY (id);


--
-- Name: conversion_events conversion_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_pkey PRIMARY KEY (id);


--
-- Name: credit_applications credit_applications_application_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_applications
    ADD CONSTRAINT credit_applications_application_number_unique UNIQUE (application_number);


--
-- Name: credit_applications credit_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_applications
    ADD CONSTRAINT credit_applications_pkey PRIMARY KEY (id);


--
-- Name: credit_note_approvals credit_note_approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_approvals
    ADD CONSTRAINT credit_note_approvals_pkey PRIMARY KEY (id);


--
-- Name: credit_note_items credit_note_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_items
    ADD CONSTRAINT credit_note_items_pkey PRIMARY KEY (id);


--
-- Name: credit_notes credit_notes_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes
    ADD CONSTRAINT credit_notes_number_unique UNIQUE (number);


--
-- Name: credit_notes credit_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes
    ADD CONSTRAINT credit_notes_pkey PRIMARY KEY (id);


--
-- Name: cross_company_users cross_company_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_pkey PRIMARY KEY (id);


--
-- Name: custom_quick_actions custom_quick_actions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_quick_actions
    ADD CONSTRAINT custom_quick_actions_pkey PRIMARY KEY (id);


--
-- Name: dashboard_activity_logs dashboard_activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_activity_logs
    ADD CONSTRAINT dashboard_activity_logs_pkey PRIMARY KEY (id);


--
-- Name: dashboard_metrics dashboard_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_metrics
    ADD CONSTRAINT dashboard_metrics_pkey PRIMARY KEY (id);


--
-- Name: dashboard_presets dashboard_presets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_presets
    ADD CONSTRAINT dashboard_presets_pkey PRIMARY KEY (id);


--
-- Name: dashboard_presets dashboard_presets_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_presets
    ADD CONSTRAINT dashboard_presets_slug_unique UNIQUE (slug);


--
-- Name: dashboard_widgets dashboard_widgets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_widgets
    ADD CONSTRAINT dashboard_widgets_pkey PRIMARY KEY (id);


--
-- Name: dashboard_widgets dashboard_widgets_widget_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_widgets
    ADD CONSTRAINT dashboard_widgets_widget_id_unique UNIQUE (widget_id);


--
-- Name: device_mappings device_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings
    ADD CONSTRAINT device_mappings_pkey PRIMARY KEY (id);


--
-- Name: device_mappings device_mappings_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings
    ADD CONSTRAINT device_mappings_uuid_unique UNIQUE (uuid);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: dunning_actions dunning_actions_action_reference_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions
    ADD CONSTRAINT dunning_actions_action_reference_unique UNIQUE (action_reference);


--
-- Name: dunning_actions dunning_actions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions
    ADD CONSTRAINT dunning_actions_pkey PRIMARY KEY (id);


--
-- Name: dunning_campaigns dunning_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_campaigns
    ADD CONSTRAINT dunning_campaigns_pkey PRIMARY KEY (id);


--
-- Name: dunning_sequences dunning_sequences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_sequences
    ADD CONSTRAINT dunning_sequences_pkey PRIMARY KEY (id);


--
-- Name: email_accounts email_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_accounts
    ADD CONSTRAINT email_accounts_pkey PRIMARY KEY (id);


--
-- Name: email_accounts email_accounts_user_id_email_address_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_accounts
    ADD CONSTRAINT email_accounts_user_id_email_address_unique UNIQUE (user_id, email_address);


--
-- Name: email_attachments email_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments
    ADD CONSTRAINT email_attachments_pkey PRIMARY KEY (id);


--
-- Name: email_folders email_folders_email_account_id_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_folders
    ADD CONSTRAINT email_folders_email_account_id_path_unique UNIQUE (email_account_id, path);


--
-- Name: email_folders email_folders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_folders
    ADD CONSTRAINT email_folders_pkey PRIMARY KEY (id);


--
-- Name: email_messages email_messages_email_account_id_uid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_email_account_id_uid_unique UNIQUE (email_account_id, uid);


--
-- Name: email_messages email_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_pkey PRIMARY KEY (id);


--
-- Name: email_signatures email_signatures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_signatures
    ADD CONSTRAINT email_signatures_pkey PRIMARY KEY (id);


--
-- Name: email_templates email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_pkey PRIMARY KEY (id);


--
-- Name: email_tracking email_tracking_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_pkey PRIMARY KEY (id);


--
-- Name: email_tracking email_tracking_tracking_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_tracking_id_unique UNIQUE (tracking_id);


--
-- Name: employee_schedules employee_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules
    ADD CONSTRAINT employee_schedules_pkey PRIMARY KEY (id);


--
-- Name: employee_schedules employee_schedules_user_id_scheduled_date_start_time_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules
    ADD CONSTRAINT employee_schedules_user_id_scheduled_date_start_time_unique UNIQUE (user_id, scheduled_date, start_time);


--
-- Name: employee_time_entries employee_time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_pkey PRIMARY KEY (id);


--
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: files files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_pkey PRIMARY KEY (id);


--
-- Name: financial_reports financial_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.financial_reports
    ADD CONSTRAINT financial_reports_pkey PRIMARY KEY (id);


--
-- Name: hr_settings_overrides hr_settings_override_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_settings_overrides
    ADD CONSTRAINT hr_settings_override_unique UNIQUE (company_id, overridable_type, overridable_id, setting_key);


--
-- Name: hr_settings_overrides hr_settings_overrides_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_settings_overrides
    ADD CONSTRAINT hr_settings_overrides_pkey PRIMARY KEY (id);


--
-- Name: in_app_notifications in_app_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.in_app_notifications
    ADD CONSTRAINT in_app_notifications_pkey PRIMARY KEY (id);


--
-- Name: invoice_items invoice_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_prefix_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_prefix_number_unique UNIQUE (prefix, number);


--
-- Name: ip_lookup_logs ip_lookup_logs_ip_address_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_lookup_logs
    ADD CONSTRAINT ip_lookup_logs_ip_address_company_id_unique UNIQUE (ip_address, company_id);


--
-- Name: ip_lookup_logs ip_lookup_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_lookup_logs
    ADD CONSTRAINT ip_lookup_logs_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: kpi_calculations kpi_calculations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_calculations
    ADD CONSTRAINT kpi_calculations_pkey PRIMARY KEY (id);


--
-- Name: kpi_targets kpi_targets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_targets
    ADD CONSTRAINT kpi_targets_pkey PRIMARY KEY (id);


--
-- Name: lead_activities lead_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_pkey PRIMARY KEY (id);


--
-- Name: lead_sources lead_sources_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_sources
    ADD CONSTRAINT lead_sources_pkey PRIMARY KEY (id);


--
-- Name: leads leads_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_email_unique UNIQUE (email);


--
-- Name: leads leads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_pkey PRIMARY KEY (id);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: mail_queue mail_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_pkey PRIMARY KEY (id);


--
-- Name: mail_queue mail_queue_tracking_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_tracking_token_unique UNIQUE (tracking_token);


--
-- Name: mail_queue mail_queue_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_uuid_unique UNIQUE (uuid);


--
-- Name: mail_templates mail_templates_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_name_unique UNIQUE (name);


--
-- Name: mail_templates mail_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_pkey PRIMARY KEY (id);


--
-- Name: marketing_campaigns marketing_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaigns
    ADD CONSTRAINT marketing_campaigns_pkey PRIMARY KEY (id);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_uuid_unique UNIQUE (uuid);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: networks networks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.networks
    ADD CONSTRAINT networks_pkey PRIMARY KEY (id);


--
-- Name: notification_logs notification_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_logs
    ADD CONSTRAINT notification_logs_pkey PRIMARY KEY (id);


--
-- Name: notification_preferences notification_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_pkey PRIMARY KEY (id);


--
-- Name: notification_preferences notification_preferences_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_user_id_unique UNIQUE (user_id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: oauth_states oauth_states_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_states
    ADD CONSTRAINT oauth_states_pkey PRIMARY KEY (id);


--
-- Name: oauth_states oauth_states_state_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_states
    ADD CONSTRAINT oauth_states_state_unique UNIQUE (state);


--
-- Name: pay_periods pay_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pay_periods
    ADD CONSTRAINT pay_periods_pkey PRIMARY KEY (id);


--
-- Name: payment_applications payment_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications
    ADD CONSTRAINT payment_applications_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_pkey PRIMARY KEY (id);


--
-- Name: payment_plans payment_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_plans
    ADD CONSTRAINT payment_plans_pkey PRIMARY KEY (id);


--
-- Name: payment_plans payment_plans_plan_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_plans
    ADD CONSTRAINT payment_plans_plan_number_unique UNIQUE (plan_number);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permission_groups permission_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_groups
    ADD CONSTRAINT permission_groups_pkey PRIMARY KEY (id);


--
-- Name: permission_groups permission_groups_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_groups
    ADD CONSTRAINT permission_groups_slug_unique UNIQUE (slug);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: physical_mail_bank_accounts physical_mail_bank_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_bank_accounts
    ADD CONSTRAINT physical_mail_bank_accounts_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_bank_accounts physical_mail_bank_accounts_postgrid_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_bank_accounts
    ADD CONSTRAINT physical_mail_bank_accounts_postgrid_id_unique UNIQUE (postgrid_id);


--
-- Name: physical_mail_cheques physical_mail_cheques_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_cheques
    ADD CONSTRAINT physical_mail_cheques_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: physical_mail_cheques physical_mail_cheques_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_cheques
    ADD CONSTRAINT physical_mail_cheques_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_contacts physical_mail_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_contacts
    ADD CONSTRAINT physical_mail_contacts_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_letters physical_mail_letters_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: physical_mail_letters physical_mail_letters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_orders physical_mail_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_orders
    ADD CONSTRAINT physical_mail_orders_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_orders physical_mail_orders_postgrid_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_orders
    ADD CONSTRAINT physical_mail_orders_postgrid_id_unique UNIQUE (postgrid_id);


--
-- Name: physical_mail_postcards physical_mail_postcards_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_postcards
    ADD CONSTRAINT physical_mail_postcards_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: physical_mail_postcards physical_mail_postcards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_postcards
    ADD CONSTRAINT physical_mail_postcards_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_return_envelopes physical_mail_return_envelopes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_return_envelopes
    ADD CONSTRAINT physical_mail_return_envelopes_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_return_envelopes physical_mail_return_envelopes_postgrid_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_return_envelopes
    ADD CONSTRAINT physical_mail_return_envelopes_postgrid_id_unique UNIQUE (postgrid_id);


--
-- Name: physical_mail_self_mailers physical_mail_self_mailers_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_self_mailers
    ADD CONSTRAINT physical_mail_self_mailers_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: physical_mail_self_mailers physical_mail_self_mailers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_self_mailers
    ADD CONSTRAINT physical_mail_self_mailers_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_settings physical_mail_settings_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_settings
    ADD CONSTRAINT physical_mail_settings_company_id_unique UNIQUE (company_id);


--
-- Name: physical_mail_settings physical_mail_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_settings
    ADD CONSTRAINT physical_mail_settings_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_templates physical_mail_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_templates
    ADD CONSTRAINT physical_mail_templates_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_templates physical_mail_templates_postgrid_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_templates
    ADD CONSTRAINT physical_mail_templates_postgrid_id_unique UNIQUE (postgrid_id);


--
-- Name: physical_mail_webhooks physical_mail_webhooks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_webhooks
    ADD CONSTRAINT physical_mail_webhooks_pkey PRIMARY KEY (id);


--
-- Name: physical_mail_webhooks physical_mail_webhooks_postgrid_event_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_webhooks
    ADD CONSTRAINT physical_mail_webhooks_postgrid_event_id_unique UNIQUE (postgrid_event_id);


--
-- Name: portal_notifications portal_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal_notifications
    ADD CONSTRAINT portal_notifications_pkey PRIMARY KEY (id);


--
-- Name: pricing_rules pricing_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing_rules
    ADD CONSTRAINT pricing_rules_pkey PRIMARY KEY (id);


--
-- Name: product_bundle_items product_bundle_items_bundle_id_product_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundle_items
    ADD CONSTRAINT product_bundle_items_bundle_id_product_id_unique UNIQUE (bundle_id, product_id);


--
-- Name: product_bundle_items product_bundle_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundle_items
    ADD CONSTRAINT product_bundle_items_pkey PRIMARY KEY (id);


--
-- Name: product_bundles product_bundles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundles
    ADD CONSTRAINT product_bundles_pkey PRIMARY KEY (id);


--
-- Name: product_tax_data product_tax_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tax_data
    ADD CONSTRAINT product_tax_data_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: project_comments project_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_comments
    ADD CONSTRAINT project_comments_pkey PRIMARY KEY (id);


--
-- Name: project_expenses project_expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_expenses
    ADD CONSTRAINT project_expenses_pkey PRIMARY KEY (id);


--
-- Name: project_files project_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_files
    ADD CONSTRAINT project_files_pkey PRIMARY KEY (id);


--
-- Name: project_members project_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_pkey PRIMARY KEY (id);


--
-- Name: project_members project_members_project_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_user_id_unique UNIQUE (project_id, user_id);


--
-- Name: project_milestones project_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones
    ADD CONSTRAINT project_milestones_pkey PRIMARY KEY (id);


--
-- Name: project_tasks project_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_pkey PRIMARY KEY (id);


--
-- Name: project_templates project_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_templates
    ADD CONSTRAINT project_templates_pkey PRIMARY KEY (id);


--
-- Name: project_time_entries project_time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_time_entries
    ADD CONSTRAINT project_time_entries_pkey PRIMARY KEY (id);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: projects projects_prefix_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_prefix_number_unique UNIQUE (prefix, number);


--
-- Name: push_subscriptions push_subscriptions_endpoint_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_endpoint_unique UNIQUE (endpoint);


--
-- Name: push_subscriptions push_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: quick_action_favorites quick_action_favorites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites
    ADD CONSTRAINT quick_action_favorites_pkey PRIMARY KEY (id);


--
-- Name: quick_action_favorites quick_action_favorites_user_id_custom_quick_action_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites
    ADD CONSTRAINT quick_action_favorites_user_id_custom_quick_action_id_unique UNIQUE (user_id, custom_quick_action_id);


--
-- Name: quick_action_favorites quick_action_favorites_user_id_system_action_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites
    ADD CONSTRAINT quick_action_favorites_user_id_system_action_unique UNIQUE (user_id, system_action);


--
-- Name: quote_approvals quote_approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_approvals
    ADD CONSTRAINT quote_approvals_pkey PRIMARY KEY (id);


--
-- Name: quote_invoice_conversions quote_invoice_conversions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_invoice_conversions
    ADD CONSTRAINT quote_invoice_conversions_pkey PRIMARY KEY (id);


--
-- Name: quote_templates quote_templates_company_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_templates
    ADD CONSTRAINT quote_templates_company_id_name_unique UNIQUE (company_id, name);


--
-- Name: quote_templates quote_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_templates
    ADD CONSTRAINT quote_templates_pkey PRIMARY KEY (id);


--
-- Name: quote_versions quote_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions
    ADD CONSTRAINT quote_versions_pkey PRIMARY KEY (id);


--
-- Name: quote_versions quote_versions_quote_id_version_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions
    ADD CONSTRAINT quote_versions_quote_id_version_number_unique UNIQUE (quote_id, version_number);


--
-- Name: quotes quotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes
    ADD CONSTRAINT quotes_pkey PRIMARY KEY (id);


--
-- Name: quotes quotes_prefix_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes
    ADD CONSTRAINT quotes_prefix_number_unique UNIQUE (prefix, number);


--
-- Name: rate_cards rate_cards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rate_cards
    ADD CONSTRAINT rate_cards_pkey PRIMARY KEY (id);


--
-- Name: recurring_invoices recurring_invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_invoices
    ADD CONSTRAINT recurring_invoices_pkey PRIMARY KEY (id);


--
-- Name: recurring recurring_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring
    ADD CONSTRAINT recurring_pkey PRIMARY KEY (id);


--
-- Name: recurring recurring_prefix_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring
    ADD CONSTRAINT recurring_prefix_number_unique UNIQUE (prefix, number);


--
-- Name: recurring_tickets recurring_tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_tickets
    ADD CONSTRAINT recurring_tickets_pkey PRIMARY KEY (id);


--
-- Name: refund_requests refund_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_pkey PRIMARY KEY (id);


--
-- Name: refund_transactions refund_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_transactions
    ADD CONSTRAINT refund_transactions_pkey PRIMARY KEY (id);


--
-- Name: report_exports report_exports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_exports
    ADD CONSTRAINT report_exports_pkey PRIMARY KEY (id);


--
-- Name: report_metrics report_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_metrics
    ADD CONSTRAINT report_metrics_pkey PRIMARY KEY (id);


--
-- Name: report_subscriptions report_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_subscriptions
    ADD CONSTRAINT report_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: report_templates report_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates
    ADD CONSTRAINT report_templates_pkey PRIMARY KEY (id);


--
-- Name: revenue_metrics revenue_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revenue_metrics
    ADD CONSTRAINT revenue_metrics_pkey PRIMARY KEY (id);


--
-- Name: rmm_client_mappings rmm_client_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT rmm_client_mappings_pkey PRIMARY KEY (id);


--
-- Name: rmm_integrations rmm_integrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_integrations
    ADD CONSTRAINT rmm_integrations_pkey PRIMARY KEY (id);


--
-- Name: bouncer_roles roles_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_roles
    ADD CONSTRAINT roles_name_unique UNIQUE (name, scope);


--
-- Name: saved_reports saved_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_reports
    ADD CONSTRAINT saved_reports_pkey PRIMARY KEY (id);


--
-- Name: scheduler_coordination scheduler_coordination_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduler_coordination
    ADD CONSTRAINT scheduler_coordination_pkey PRIMARY KEY (id);


--
-- Name: scheduler_coordination scheduler_unique_execution; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduler_coordination
    ADD CONSTRAINT scheduler_unique_execution UNIQUE (job_name, schedule_key);


--
-- Name: service_tax_rates service_tax_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_tax_rates
    ADD CONSTRAINT service_tax_rates_pkey PRIMARY KEY (id);


--
-- Name: services services_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_company_id_unique UNIQUE (company_id);


--
-- Name: settings_configurations settings_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings_configurations
    ADD CONSTRAINT settings_configurations_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: shifts shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_pkey PRIMARY KEY (id);


--
-- Name: slas slas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slas
    ADD CONSTRAINT slas_pkey PRIMARY KEY (id);


--
-- Name: subscription_plans subscription_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans
    ADD CONSTRAINT subscription_plans_pkey PRIMARY KEY (id);


--
-- Name: subscription_plans subscription_plans_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans
    ADD CONSTRAINT subscription_plans_slug_unique UNIQUE (slug);


--
-- Name: subscription_plans subscription_plans_stripe_price_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans
    ADD CONSTRAINT subscription_plans_stripe_price_id_unique UNIQUE (stripe_price_id);


--
-- Name: subsidiary_permissions subsidiary_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_pkey PRIMARY KEY (id);


--
-- Name: suspicious_login_attempts suspicious_login_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suspicious_login_attempts
    ADD CONSTRAINT suspicious_login_attempts_pkey PRIMARY KEY (id);


--
-- Name: suspicious_login_attempts suspicious_login_attempts_verification_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suspicious_login_attempts
    ADD CONSTRAINT suspicious_login_attempts_verification_token_unique UNIQUE (verification_token);


--
-- Name: tags tags_company_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_company_id_name_unique UNIQUE (company_id, name);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: task_checklist_items task_checklist_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_checklist_items
    ADD CONSTRAINT task_checklist_items_pkey PRIMARY KEY (id);


--
-- Name: task_dependencies task_dependencies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies
    ADD CONSTRAINT task_dependencies_pkey PRIMARY KEY (id);


--
-- Name: task_dependencies task_dependencies_task_id_depends_on_task_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies
    ADD CONSTRAINT task_dependencies_task_id_depends_on_task_id_unique UNIQUE (task_id, depends_on_task_id);


--
-- Name: task_watchers task_watchers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers
    ADD CONSTRAINT task_watchers_pkey PRIMARY KEY (id);


--
-- Name: task_watchers task_watchers_task_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers
    ADD CONSTRAINT task_watchers_task_id_user_id_unique UNIQUE (task_id, user_id);


--
-- Name: tax_api_query_cache tax_api_query_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_query_cache
    ADD CONSTRAINT tax_api_query_cache_pkey PRIMARY KEY (id);


--
-- Name: tax_api_settings tax_api_settings_company_id_provider_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_settings
    ADD CONSTRAINT tax_api_settings_company_id_provider_unique UNIQUE (company_id, provider);


--
-- Name: tax_api_settings tax_api_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_settings
    ADD CONSTRAINT tax_api_settings_pkey PRIMARY KEY (id);


--
-- Name: tax_calculations tax_calculations_calculation_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_calculation_id_unique UNIQUE (calculation_id);


--
-- Name: tax_calculations tax_calculations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_pkey PRIMARY KEY (id);


--
-- Name: tax_exemptions tax_exemptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_exemptions
    ADD CONSTRAINT tax_exemptions_pkey PRIMARY KEY (id);


--
-- Name: tax_jurisdictions tax_jurisdictions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_jurisdictions
    ADD CONSTRAINT tax_jurisdictions_pkey PRIMARY KEY (id);


--
-- Name: tax_profiles tax_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_profiles
    ADD CONSTRAINT tax_profiles_pkey PRIMARY KEY (id);


--
-- Name: taxes taxes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes
    ADD CONSTRAINT taxes_pkey PRIMARY KEY (id);


--
-- Name: ticket_assignments ticket_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_pkey PRIMARY KEY (id);


--
-- Name: ticket_calendar_events ticket_calendar_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_calendar_events
    ADD CONSTRAINT ticket_calendar_events_pkey PRIMARY KEY (id);


--
-- Name: ticket_comment_attachments ticket_comment_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comment_attachments
    ADD CONSTRAINT ticket_comment_attachments_pkey PRIMARY KEY (id);


--
-- Name: ticket_comments ticket_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_pkey PRIMARY KEY (id);


--
-- Name: ticket_priority_queue ticket_priority_queue_company_id_ticket_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queue
    ADD CONSTRAINT ticket_priority_queue_company_id_ticket_id_unique UNIQUE (company_id, ticket_id);


--
-- Name: ticket_priority_queue ticket_priority_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queue
    ADD CONSTRAINT ticket_priority_queue_pkey PRIMARY KEY (id);


--
-- Name: ticket_priority_queues ticket_priority_queues_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queues
    ADD CONSTRAINT ticket_priority_queues_pkey PRIMARY KEY (id);


--
-- Name: ticket_ratings ticket_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings
    ADD CONSTRAINT ticket_ratings_pkey PRIMARY KEY (id);


--
-- Name: ticket_replies ticket_replies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_replies
    ADD CONSTRAINT ticket_replies_pkey PRIMARY KEY (id);


--
-- Name: ticket_status_transitions ticket_status_transitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_status_transitions
    ADD CONSTRAINT ticket_status_transitions_pkey PRIMARY KEY (id);


--
-- Name: ticket_status_transitions ticket_status_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_status_transitions
    ADD CONSTRAINT ticket_status_unique UNIQUE (company_id, from_status, to_status);


--
-- Name: ticket_templates ticket_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_templates
    ADD CONSTRAINT ticket_templates_pkey PRIMARY KEY (id);


--
-- Name: ticket_time_entries ticket_time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_time_entries
    ADD CONSTRAINT ticket_time_entries_pkey PRIMARY KEY (id);


--
-- Name: ticket_watchers ticket_watchers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT ticket_watchers_pkey PRIMARY KEY (id);


--
-- Name: ticket_workflows ticket_workflows_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_workflows
    ADD CONSTRAINT ticket_workflows_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_prefix_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_prefix_number_unique UNIQUE (prefix, number);


--
-- Name: time_entries time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_pkey PRIMARY KEY (id);


--
-- Name: time_entry_templates time_entry_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_templates
    ADD CONSTRAINT time_entry_templates_pkey PRIMARY KEY (id);


--
-- Name: time_off_requests time_off_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_off_requests
    ADD CONSTRAINT time_off_requests_pkey PRIMARY KEY (id);


--
-- Name: trusted_devices trusted_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trusted_devices
    ADD CONSTRAINT trusted_devices_pkey PRIMARY KEY (id);


--
-- Name: contract_configurations unique_company_config; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations
    ADD CONSTRAINT unique_company_config UNIQUE (company_id);


--
-- Name: settings_configurations unique_company_domain_category; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings_configurations
    ADD CONSTRAINT unique_company_domain_category UNIQUE (company_id, domain, category);


--
-- Name: rmm_integrations unique_company_rmm_type; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_integrations
    ADD CONSTRAINT unique_company_rmm_type UNIQUE (company_id, rmm_type);


--
-- Name: contract_asset_assignments unique_contract_asset_assignment; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT unique_contract_asset_assignment UNIQUE (contract_id, asset_id);


--
-- Name: contract_billing_calculations unique_contract_billing_period; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT unique_contract_billing_period UNIQUE (contract_id, billing_period_start, billing_period_end);


--
-- Name: contract_contact_assignments unique_contract_contact_assignment; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT unique_contract_contact_assignment UNIQUE (contract_id, contact_id);


--
-- Name: contract_schedules unique_contract_schedule_letter; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT unique_contract_schedule_letter UNIQUE (contract_id, schedule_letter);


--
-- Name: contract_type_form_mappings unique_contract_type_section; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_form_mappings
    ADD CONSTRAINT unique_contract_type_section UNIQUE (company_id, contract_type_slug, section_slug);


--
-- Name: contract_view_definitions unique_contract_view; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_view_definitions
    ADD CONSTRAINT unique_contract_view UNIQUE (company_id, contract_type_slug, view_type);


--
-- Name: cross_company_users unique_cross_company_user; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT unique_cross_company_user UNIQUE (user_id, company_id);


--
-- Name: company_hierarchies unique_hierarchy_relationship; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_hierarchies
    ADD CONSTRAINT unique_hierarchy_relationship UNIQUE (ancestor_id, descendant_id);


--
-- Name: rmm_client_mappings unique_integration_client_mapping; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT unique_integration_client_mapping UNIQUE (integration_id, client_id);


--
-- Name: rmm_client_mappings unique_integration_rmm_client_mapping; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT unique_integration_rmm_client_mapping UNIQUE (integration_id, rmm_client_id);


--
-- Name: contract_status_transitions unique_status_transition; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_transitions
    ADD CONSTRAINT unique_status_transition UNIQUE (company_id, from_status_slug, to_status_slug);


--
-- Name: subsidiary_permissions unique_subsidiary_permission; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT unique_subsidiary_permission UNIQUE (granter_company_id, grantee_company_id, user_id, resource_type, permission_type);


--
-- Name: ticket_watchers unique_ticket_watcher; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT unique_ticket_watcher UNIQUE (company_id, ticket_id, email);


--
-- Name: usage_alerts usage_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_alerts
    ADD CONSTRAINT usage_alerts_pkey PRIMARY KEY (id);


--
-- Name: usage_buckets usage_buckets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_buckets
    ADD CONSTRAINT usage_buckets_pkey PRIMARY KEY (id);


--
-- Name: usage_pools usage_pools_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_pools
    ADD CONSTRAINT usage_pools_pkey PRIMARY KEY (id);


--
-- Name: usage_pools usage_pools_pool_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_pools
    ADD CONSTRAINT usage_pools_pool_code_unique UNIQUE (pool_code);


--
-- Name: usage_records usage_records_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_records
    ADD CONSTRAINT usage_records_pkey PRIMARY KEY (id);


--
-- Name: usage_tiers usage_tiers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_tiers
    ADD CONSTRAINT usage_tiers_pkey PRIMARY KEY (id);


--
-- Name: user_clients user_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_clients
    ADD CONSTRAINT user_clients_pkey PRIMARY KEY (id);


--
-- Name: user_clients user_clients_user_id_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_clients
    ADD CONSTRAINT user_clients_user_id_client_id_unique UNIQUE (user_id, client_id);


--
-- Name: user_dashboard_configs user_dashboard_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_dashboard_configs
    ADD CONSTRAINT user_dashboard_configs_pkey PRIMARY KEY (id);


--
-- Name: user_dashboard_configs user_dashboard_configs_user_id_dashboard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_dashboard_configs
    ADD CONSTRAINT user_dashboard_configs_user_id_dashboard_name_unique UNIQUE (user_id, dashboard_name);


--
-- Name: user_favorite_clients user_favorite_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_favorite_clients
    ADD CONSTRAINT user_favorite_clients_pkey PRIMARY KEY (id);


--
-- Name: user_favorite_clients user_favorite_clients_user_id_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_favorite_clients
    ADD CONSTRAINT user_favorite_clients_user_id_client_id_unique UNIQUE (user_id, client_id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_user_id_role_id_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_role_id_company_id_unique UNIQUE (user_id, role_id, company_id);


--
-- Name: user_settings user_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings
    ADD CONSTRAINT user_settings_pkey PRIMARY KEY (id);


--
-- Name: user_widget_instances user_widget_instances_instance_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_widget_instances
    ADD CONSTRAINT user_widget_instances_instance_id_unique UNIQUE (instance_id);


--
-- Name: user_widget_instances user_widget_instances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_widget_instances
    ADD CONSTRAINT user_widget_instances_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: vendors vendors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors
    ADD CONSTRAINT vendors_pkey PRIMARY KEY (id);


--
-- Name: widget_data_cache widget_data_cache_cache_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_data_cache
    ADD CONSTRAINT widget_data_cache_cache_key_unique UNIQUE (cache_key);


--
-- Name: widget_data_cache widget_data_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_data_cache
    ADD CONSTRAINT widget_data_cache_pkey PRIMARY KEY (id);


--
-- Name: accounts_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_archived_at_index ON public.accounts USING btree (archived_at);


--
-- Name: accounts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_company_id_index ON public.accounts USING btree (company_id);


--
-- Name: accounts_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_company_id_type_index ON public.accounts USING btree (company_id, type);


--
-- Name: accounts_currency_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_currency_code_index ON public.accounts USING btree (currency_code);


--
-- Name: accounts_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_name_index ON public.accounts USING btree (name);


--
-- Name: accounts_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_type_index ON public.accounts USING btree (type);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: asset_depreciations_company_id_asset_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_depreciations_company_id_asset_id_index ON public.asset_depreciations USING btree (company_id, asset_id);


--
-- Name: asset_maintenance_company_id_asset_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_maintenance_company_id_asset_id_index ON public.asset_maintenance USING btree (company_id, asset_id);


--
-- Name: asset_maintenance_company_id_scheduled_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_maintenance_company_id_scheduled_date_index ON public.asset_maintenance USING btree (company_id, scheduled_date);


--
-- Name: asset_maintenance_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_maintenance_company_id_status_index ON public.asset_maintenance USING btree (company_id, status);


--
-- Name: asset_warranties_company_id_asset_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_warranties_company_id_asset_id_index ON public.asset_warranties USING btree (company_id, asset_id);


--
-- Name: asset_warranties_company_id_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_warranties_company_id_end_date_index ON public.asset_warranties USING btree (company_id, end_date);


--
-- Name: asset_warranties_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX asset_warranties_company_id_status_index ON public.asset_warranties USING btree (company_id, status);


--
-- Name: assets_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_archived_at_index ON public.assets USING btree (archived_at);


--
-- Name: assets_auto_assigned_support_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_auto_assigned_support_index ON public.assets USING btree (auto_assigned_support);


--
-- Name: assets_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_client_id_index ON public.assets USING btree (client_id);


--
-- Name: assets_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_client_id_status_index ON public.assets USING btree (client_id, status);


--
-- Name: assets_client_id_support_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_client_id_support_status_index ON public.assets USING btree (client_id, support_status);


--
-- Name: assets_client_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_client_id_type_index ON public.assets USING btree (client_id, type);


--
-- Name: assets_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_company_id_client_id_index ON public.assets USING btree (company_id, client_id);


--
-- Name: assets_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_company_id_index ON public.assets USING btree (company_id);


--
-- Name: assets_company_id_support_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_company_id_support_status_index ON public.assets USING btree (company_id, support_status);


--
-- Name: assets_ip_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_ip_index ON public.assets USING btree (ip);


--
-- Name: assets_mac_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_mac_index ON public.assets USING btree (mac);


--
-- Name: assets_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_name_index ON public.assets USING btree (name);


--
-- Name: assets_next_maintenance_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_next_maintenance_date_index ON public.assets USING btree (next_maintenance_date);


--
-- Name: assets_serial_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_serial_index ON public.assets USING btree (serial);


--
-- Name: assets_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_status_index ON public.assets USING btree (status);


--
-- Name: assets_support_last_evaluated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_support_last_evaluated_at_index ON public.assets USING btree (support_last_evaluated_at);


--
-- Name: assets_support_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_support_status_index ON public.assets USING btree (support_status);


--
-- Name: assets_support_status_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_support_status_type_index ON public.assets USING btree (support_status, type);


--
-- Name: assets_supporting_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_supporting_contract_id_index ON public.assets USING btree (supporting_contract_id);


--
-- Name: assets_supporting_contract_id_support_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_supporting_contract_id_support_status_index ON public.assets USING btree (supporting_contract_id, support_status);


--
-- Name: assets_supporting_schedule_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_supporting_schedule_id_index ON public.assets USING btree (supporting_schedule_id);


--
-- Name: assets_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_type_index ON public.assets USING btree (type);


--
-- Name: assets_warranty_expire_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_warranty_expire_index ON public.assets USING btree (warranty_expire);


--
-- Name: assigned_roles_entity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assigned_roles_entity_index ON public.bouncer_assigned_roles USING btree (entity_id, entity_type, scope);


--
-- Name: attribution_touchpoints_campaign_id_touched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribution_touchpoints_campaign_id_touched_at_index ON public.attribution_touchpoints USING btree (campaign_id, touched_at);


--
-- Name: attribution_touchpoints_client_id_touched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribution_touchpoints_client_id_touched_at_index ON public.attribution_touchpoints USING btree (client_id, touched_at);


--
-- Name: attribution_touchpoints_contact_id_touched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribution_touchpoints_contact_id_touched_at_index ON public.attribution_touchpoints USING btree (contact_id, touched_at);


--
-- Name: attribution_touchpoints_lead_id_touched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribution_touchpoints_lead_id_touched_at_index ON public.attribution_touchpoints USING btree (lead_id, touched_at);


--
-- Name: attribution_touchpoints_touchpoint_type_touched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribution_touchpoints_touchpoint_type_touched_at_index ON public.attribution_touchpoints USING btree (touchpoint_type, touched_at);


--
-- Name: audit_logs_company_id_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_company_id_event_type_index ON public.audit_logs USING btree (company_id, event_type);


--
-- Name: audit_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_company_id_index ON public.audit_logs USING btree (company_id);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);


--
-- Name: audit_logs_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_event_type_index ON public.audit_logs USING btree (event_type);


--
-- Name: audit_logs_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_ip_address_index ON public.audit_logs USING btree (ip_address);


--
-- Name: audit_logs_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_model_id_index ON public.audit_logs USING btree (model_id);


--
-- Name: audit_logs_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_model_type_index ON public.audit_logs USING btree (model_type);


--
-- Name: audit_logs_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_model_type_model_id_index ON public.audit_logs USING btree (model_type, model_id);


--
-- Name: audit_logs_severity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_severity_index ON public.audit_logs USING btree (severity);


--
-- Name: audit_logs_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_user_id_index ON public.audit_logs USING btree (user_id);


--
-- Name: bouncer_abilities_scope_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_abilities_scope_index ON public.bouncer_abilities USING btree (scope);


--
-- Name: bouncer_assigned_roles_role_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_assigned_roles_role_id_index ON public.bouncer_assigned_roles USING btree (role_id);


--
-- Name: bouncer_assigned_roles_scope_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_assigned_roles_scope_index ON public.bouncer_assigned_roles USING btree (scope);


--
-- Name: bouncer_permissions_ability_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_permissions_ability_id_index ON public.bouncer_permissions USING btree (ability_id);


--
-- Name: bouncer_permissions_scope_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_permissions_scope_index ON public.bouncer_permissions USING btree (scope);


--
-- Name: bouncer_roles_scope_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bouncer_roles_scope_index ON public.bouncer_roles USING btree (scope);


--
-- Name: campaign_enrollments_campaign_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_enrollments_campaign_id_status_index ON public.campaign_enrollments USING btree (campaign_id, status);


--
-- Name: campaign_enrollments_contact_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_enrollments_contact_id_status_index ON public.campaign_enrollments USING btree (contact_id, status);


--
-- Name: campaign_enrollments_lead_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_enrollments_lead_id_status_index ON public.campaign_enrollments USING btree (lead_id, status);


--
-- Name: campaign_enrollments_next_send_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_enrollments_next_send_at_index ON public.campaign_enrollments USING btree (next_send_at);


--
-- Name: campaign_sequences_campaign_id_step_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_sequences_campaign_id_step_number_index ON public.campaign_sequences USING btree (campaign_id, step_number);


--
-- Name: categories_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_archived_at_index ON public.categories USING btree (archived_at);


--
-- Name: categories_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_code_index ON public.categories USING btree (code);


--
-- Name: categories_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_company_id_index ON public.categories USING btree (company_id);


--
-- Name: categories_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_company_id_is_active_index ON public.categories USING btree (company_id, is_active);


--
-- Name: categories_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_is_active_index ON public.categories USING btree (is_active);


--
-- Name: categories_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_name_index ON public.categories USING btree (name);


--
-- Name: categories_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_parent_id_index ON public.categories USING btree (parent_id);


--
-- Name: categories_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_slug_index ON public.categories USING btree (slug);


--
-- Name: categories_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_sort_order_index ON public.categories USING btree (sort_order);


--
-- Name: categories_type_gin_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_type_gin_index ON public.categories USING gin (type);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: cbc_auto_invoice_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_auto_invoice_status_idx ON public.contract_billing_calculations USING btree (auto_invoice, status);


--
-- Name: cbc_billing_period_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_billing_period_idx ON public.contract_billing_calculations USING btree (billing_period_start, billing_period_end);


--
-- Name: cbc_billing_type_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_billing_type_idx ON public.contract_billing_calculations USING btree (billing_type);


--
-- Name: cbc_calculated_at_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_calculated_at_idx ON public.contract_billing_calculations USING btree (calculated_at);


--
-- Name: cbc_company_contract_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_company_contract_idx ON public.contract_billing_calculations USING btree (company_id, contract_id);


--
-- Name: cbc_contract_start_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_contract_start_idx ON public.contract_billing_calculations USING btree (contract_id, billing_period_start);


--
-- Name: cbc_has_disputes_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_has_disputes_idx ON public.contract_billing_calculations USING btree (has_disputes);


--
-- Name: cbc_invoice_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_invoice_id_idx ON public.contract_billing_calculations USING btree (invoice_id);


--
-- Name: cbc_status_end_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_status_end_idx ON public.contract_billing_calculations USING btree (status, billing_period_end);


--
-- Name: cbc_total_amount_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cbc_total_amount_idx ON public.contract_billing_calculations USING btree (total_amount);


--
-- Name: client_addresses_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_addresses_company_id_client_id_index ON public.client_addresses USING btree (company_id, client_id);


--
-- Name: client_addresses_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_addresses_company_id_type_index ON public.client_addresses USING btree (company_id, type);


--
-- Name: client_calendar_events_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_calendar_events_company_id_client_id_index ON public.client_calendar_events USING btree (company_id, client_id);


--
-- Name: client_calendar_events_company_id_start_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_calendar_events_company_id_start_time_index ON public.client_calendar_events USING btree (company_id, start_time);


--
-- Name: client_calendar_events_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_calendar_events_company_id_type_index ON public.client_calendar_events USING btree (company_id, type);


--
-- Name: client_certificates_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_certificates_company_id_client_id_index ON public.client_certificates USING btree (company_id, client_id);


--
-- Name: client_certificates_company_id_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_certificates_company_id_expiry_date_index ON public.client_certificates USING btree (company_id, expiry_date);


--
-- Name: client_certificates_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_certificates_company_id_status_index ON public.client_certificates USING btree (company_id, status);


--
-- Name: client_contacts_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_contacts_company_id_client_id_index ON public.client_contacts USING btree (company_id, client_id);


--
-- Name: client_contacts_company_id_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_contacts_company_id_email_index ON public.client_contacts USING btree (company_id, email);


--
-- Name: client_contacts_company_id_is_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_contacts_company_id_is_primary_index ON public.client_contacts USING btree (company_id, is_primary);


--
-- Name: client_credentials_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credentials_company_id_client_id_index ON public.client_credentials USING btree (company_id, client_id);


--
-- Name: client_credentials_company_id_service_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credentials_company_id_service_name_index ON public.client_credentials USING btree (company_id, service_name);


--
-- Name: client_credit_applications_applicable_type_applicable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credit_applications_applicable_type_applicable_id_index ON public.client_credit_applications USING btree (applicable_type, applicable_id);


--
-- Name: client_credit_applications_client_credit_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credit_applications_client_credit_id_is_active_index ON public.client_credit_applications USING btree (client_credit_id, is_active);


--
-- Name: client_credit_applications_company_id_applied_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credit_applications_company_id_applied_date_index ON public.client_credit_applications USING btree (company_id, applied_date);


--
-- Name: client_credits_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credits_client_id_status_index ON public.client_credits USING btree (client_id, status);


--
-- Name: client_credits_company_id_credit_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credits_company_id_credit_date_index ON public.client_credits USING btree (company_id, credit_date);


--
-- Name: client_credits_expiry_date_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credits_expiry_date_status_index ON public.client_credits USING btree (expiry_date, status);


--
-- Name: client_credits_source_type_source_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_credits_source_type_source_id_index ON public.client_credits USING btree (source_type, source_id);


--
-- Name: client_documents_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_documents_company_id_client_id_index ON public.client_documents USING btree (company_id, client_id);


--
-- Name: client_documents_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_documents_company_id_type_index ON public.client_documents USING btree (company_id, type);


--
-- Name: client_domains_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_domains_company_id_client_id_index ON public.client_domains USING btree (company_id, client_id);


--
-- Name: client_domains_company_id_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_domains_company_id_domain_index ON public.client_domains USING btree (company_id, domain);


--
-- Name: client_domains_company_id_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_domains_company_id_expiry_date_index ON public.client_domains USING btree (company_id, expiry_date);


--
-- Name: client_files_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_files_company_id_category_index ON public.client_files USING btree (company_id, category);


--
-- Name: client_files_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_files_company_id_client_id_index ON public.client_files USING btree (company_id, client_id);


--
-- Name: client_it_documentation_authored_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_authored_by_index ON public.client_it_documentation USING btree (authored_by);


--
-- Name: client_it_documentation_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_company_id_client_id_index ON public.client_it_documentation USING btree (company_id, client_id);


--
-- Name: client_it_documentation_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_company_id_is_active_index ON public.client_it_documentation USING btree (company_id, is_active);


--
-- Name: client_it_documentation_company_id_is_template_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_company_id_is_template_index ON public.client_it_documentation USING btree (company_id, is_template);


--
-- Name: client_it_documentation_company_id_it_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_company_id_it_category_index ON public.client_it_documentation USING btree (company_id, it_category);


--
-- Name: client_it_documentation_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_company_id_status_index ON public.client_it_documentation USING btree (company_id, status);


--
-- Name: client_it_documentation_last_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_last_reviewed_at_index ON public.client_it_documentation USING btree (last_reviewed_at);


--
-- Name: client_it_documentation_next_review_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_next_review_at_index ON public.client_it_documentation USING btree (next_review_at);


--
-- Name: client_it_documentation_parent_document_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_it_documentation_parent_document_id_index ON public.client_it_documentation USING btree (parent_document_id);


--
-- Name: client_licenses_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_licenses_company_id_client_id_index ON public.client_licenses USING btree (company_id, client_id);


--
-- Name: client_licenses_company_id_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_licenses_company_id_expiry_date_index ON public.client_licenses USING btree (company_id, expiry_date);


--
-- Name: client_licenses_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_licenses_company_id_status_index ON public.client_licenses USING btree (company_id, status);


--
-- Name: client_networks_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_networks_company_id_client_id_index ON public.client_networks USING btree (company_id, client_id);


--
-- Name: client_networks_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_networks_company_id_type_index ON public.client_networks USING btree (company_id, type);


--
-- Name: client_quotes_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_quotes_company_id_client_id_index ON public.client_quotes USING btree (company_id, client_id);


--
-- Name: client_quotes_company_id_quote_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_quotes_company_id_quote_date_index ON public.client_quotes USING btree (company_id, quote_date);


--
-- Name: client_quotes_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_quotes_company_id_status_index ON public.client_quotes USING btree (company_id, status);


--
-- Name: client_racks_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_racks_company_id_client_id_index ON public.client_racks USING btree (company_id, client_id);


--
-- Name: client_racks_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_racks_company_id_status_index ON public.client_racks USING btree (company_id, status);


--
-- Name: client_recurring_invoices_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_recurring_invoices_company_id_client_id_index ON public.client_recurring_invoices USING btree (company_id, client_id);


--
-- Name: client_recurring_invoices_company_id_next_invoice_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_recurring_invoices_company_id_next_invoice_date_index ON public.client_recurring_invoices USING btree (company_id, next_invoice_date);


--
-- Name: client_recurring_invoices_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_recurring_invoices_company_id_status_index ON public.client_recurring_invoices USING btree (company_id, status);


--
-- Name: client_services_activated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_activated_at_index ON public.client_services USING btree (activated_at);


--
-- Name: client_services_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_company_id_client_id_index ON public.client_services USING btree (company_id, client_id);


--
-- Name: client_services_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_company_id_status_index ON public.client_services USING btree (company_id, status);


--
-- Name: client_services_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_company_id_type_index ON public.client_services USING btree (company_id, service_type);


--
-- Name: client_services_health_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_health_score_index ON public.client_services USING btree (health_score);


--
-- Name: client_services_provisioning_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_services_provisioning_status_index ON public.client_services USING btree (provisioning_status);


--
-- Name: client_tags_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_tags_company_id_client_id_index ON public.client_tags USING btree (company_id, client_id);


--
-- Name: client_tags_company_id_tag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_tags_company_id_tag_id_index ON public.client_tags USING btree (company_id, tag_id);


--
-- Name: client_trips_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_trips_company_id_client_id_index ON public.client_trips USING btree (company_id, client_id);


--
-- Name: client_trips_company_id_start_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_trips_company_id_start_time_index ON public.client_trips USING btree (company_id, start_time);


--
-- Name: client_trips_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_trips_company_id_status_index ON public.client_trips USING btree (company_id, status);


--
-- Name: client_vendors_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_vendors_company_id_client_id_index ON public.client_vendors USING btree (company_id, client_id);


--
-- Name: client_vendors_company_id_relationship_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_vendors_company_id_relationship_index ON public.client_vendors USING btree (company_id, relationship);


--
-- Name: clients_accessed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_accessed_at_index ON public.clients USING btree (accessed_at);


--
-- Name: clients_company_id_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_archived_at_index ON public.clients USING btree (company_id, archived_at);


--
-- Name: clients_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_index ON public.clients USING btree (company_id);


--
-- Name: clients_company_id_lead_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_lead_index ON public.clients USING btree (company_id, lead);


--
-- Name: clients_company_id_sla_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_sla_id_index ON public.clients USING btree (company_id, sla_id);


--
-- Name: clients_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_status_index ON public.clients USING btree (company_id, status);


--
-- Name: clients_company_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_name_index ON public.clients USING btree (company_name);


--
-- Name: clients_lead_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_lead_index ON public.clients USING btree (lead);


--
-- Name: clients_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_status_created_at_index ON public.clients USING btree (status, created_at);


--
-- Name: clients_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_type_index ON public.clients USING btree (type);


--
-- Name: communication_logs_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communication_logs_client_id_created_at_index ON public.communication_logs USING btree (client_id, created_at);


--
-- Name: communication_logs_follow_up_required_follow_up_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communication_logs_follow_up_required_follow_up_date_index ON public.communication_logs USING btree (follow_up_required, follow_up_date);


--
-- Name: communication_logs_type_channel_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communication_logs_type_channel_index ON public.communication_logs USING btree (type, channel);


--
-- Name: companies_billing_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_billing_idx ON public.companies USING btree (billing_parent_id);


--
-- Name: companies_currency_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_currency_index ON public.companies USING btree (currency);


--
-- Name: companies_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_email_index ON public.companies USING btree (email);


--
-- Name: companies_hierarchy_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_hierarchy_idx ON public.companies USING btree (parent_company_id, company_type);


--
-- Name: companies_level_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_level_idx ON public.companies USING btree (organizational_level);


--
-- Name: companies_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX companies_name_index ON public.companies USING btree (name);


--
-- Name: company_customizations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_customizations_company_id_index ON public.company_customizations USING btree (company_id);


--
-- Name: company_hierarchies_ancestor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_hierarchies_ancestor_id_index ON public.company_hierarchies USING btree (ancestor_id);


--
-- Name: company_hierarchies_descendant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_hierarchies_descendant_id_index ON public.company_hierarchies USING btree (descendant_id);


--
-- Name: company_mail_settings_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_mail_settings_is_active_index ON public.company_mail_settings USING btree (is_active);


--
-- Name: company_subscriptions_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_subscriptions_company_id_status_index ON public.company_subscriptions USING btree (company_id, status);


--
-- Name: company_subscriptions_current_period_end_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_subscriptions_current_period_end_index ON public.company_subscriptions USING btree (current_period_end);


--
-- Name: company_subscriptions_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_subscriptions_status_index ON public.company_subscriptions USING btree (status);


--
-- Name: company_subscriptions_stripe_subscription_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_subscriptions_stripe_subscription_id_index ON public.company_subscriptions USING btree (stripe_subscription_id);


--
-- Name: company_subscriptions_trial_ends_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_subscriptions_trial_ends_at_index ON public.company_subscriptions USING btree (trial_ends_at);


--
-- Name: contacts_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_archived_at_index ON public.contacts USING btree (archived_at);


--
-- Name: contacts_client_id_billing_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_client_id_billing_index ON public.contacts USING btree (client_id, billing);


--
-- Name: contacts_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_client_id_index ON public.contacts USING btree (client_id);


--
-- Name: contacts_client_id_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_client_id_primary_index ON public.contacts USING btree (client_id, "primary");


--
-- Name: contacts_client_id_technical_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_client_id_technical_index ON public.contacts USING btree (client_id, technical);


--
-- Name: contacts_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_company_id_client_id_index ON public.contacts USING btree (company_id, client_id);


--
-- Name: contacts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_company_id_index ON public.contacts USING btree (company_id);


--
-- Name: contacts_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_email_index ON public.contacts USING btree (email);


--
-- Name: contacts_important_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_important_index ON public.contacts USING btree (important);


--
-- Name: contacts_is_after_hours_contact_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_is_after_hours_contact_index ON public.contacts USING btree (is_after_hours_contact);


--
-- Name: contacts_is_emergency_contact_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_is_emergency_contact_index ON public.contacts USING btree (is_emergency_contact);


--
-- Name: contacts_language_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_language_index ON public.contacts USING btree (language);


--
-- Name: contacts_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_name_index ON public.contacts USING btree (name);


--
-- Name: contacts_preferred_contact_method_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_preferred_contact_method_index ON public.contacts USING btree (preferred_contact_method);


--
-- Name: contacts_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_primary_index ON public.contacts USING btree ("primary");


--
-- Name: contacts_timezone_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_timezone_index ON public.contacts USING btree (timezone);


--
-- Name: contract_action_buttons_company_id_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_action_buttons_company_id_is_active_sort_order_index ON public.contract_action_buttons USING btree (company_id, is_active, sort_order);


--
-- Name: contract_amendments_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_amendments_company_id_status_index ON public.contract_amendments USING btree (company_id, status);


--
-- Name: contract_amendments_contract_id_amendment_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_amendments_contract_id_amendment_number_index ON public.contract_amendments USING btree (contract_id, amendment_number);


--
-- Name: contract_amendments_effective_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_amendments_effective_date_index ON public.contract_amendments USING btree (effective_date);


--
-- Name: contract_approvals_approver_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_approver_id_index ON public.contract_approvals USING btree (approver_id);


--
-- Name: contract_approvals_approver_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_approver_id_status_index ON public.contract_approvals USING btree (approver_id, status);


--
-- Name: contract_approvals_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_company_id_contract_id_index ON public.contract_approvals USING btree (company_id, contract_id);


--
-- Name: contract_approvals_company_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_company_id_due_date_index ON public.contract_approvals USING btree (company_id, due_date);


--
-- Name: contract_approvals_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_company_id_index ON public.contract_approvals USING btree (company_id);


--
-- Name: contract_approvals_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_company_id_status_index ON public.contract_approvals USING btree (company_id, status);


--
-- Name: contract_approvals_contract_id_approval_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_contract_id_approval_order_index ON public.contract_approvals USING btree (contract_id, approval_order);


--
-- Name: contract_approvals_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_contract_id_index ON public.contract_approvals USING btree (contract_id);


--
-- Name: contract_approvals_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_approvals_status_index ON public.contract_approvals USING btree (status);


--
-- Name: contract_asset_assignments_asset_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_asset_id_index ON public.contract_asset_assignments USING btree (asset_id);


--
-- Name: contract_asset_assignments_asset_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_asset_id_status_index ON public.contract_asset_assignments USING btree (asset_id, status);


--
-- Name: contract_asset_assignments_auto_assigned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_auto_assigned_index ON public.contract_asset_assignments USING btree (auto_assigned);


--
-- Name: contract_asset_assignments_billing_frequency_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_billing_frequency_status_index ON public.contract_asset_assignments USING btree (billing_frequency, status);


--
-- Name: contract_asset_assignments_company_id_asset_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_company_id_asset_id_index ON public.contract_asset_assignments USING btree (company_id, asset_id);


--
-- Name: contract_asset_assignments_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_company_id_contract_id_index ON public.contract_asset_assignments USING btree (company_id, contract_id);


--
-- Name: contract_asset_assignments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_company_id_index ON public.contract_asset_assignments USING btree (company_id);


--
-- Name: contract_asset_assignments_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_contract_id_index ON public.contract_asset_assignments USING btree (contract_id);


--
-- Name: contract_asset_assignments_contract_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_contract_id_status_index ON public.contract_asset_assignments USING btree (contract_id, status);


--
-- Name: contract_asset_assignments_next_billing_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_next_billing_date_index ON public.contract_asset_assignments USING btree (next_billing_date);


--
-- Name: contract_asset_assignments_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_start_date_end_date_index ON public.contract_asset_assignments USING btree (start_date, end_date);


--
-- Name: contract_asset_assignments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_asset_assignments_status_index ON public.contract_asset_assignments USING btree (status);


--
-- Name: contract_billing_calculations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_calculations_company_id_index ON public.contract_billing_calculations USING btree (company_id);


--
-- Name: contract_billing_calculations_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_calculations_contract_id_index ON public.contract_billing_calculations USING btree (contract_id);


--
-- Name: contract_billing_calculations_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_calculations_invoice_id_index ON public.contract_billing_calculations USING btree (invoice_id);


--
-- Name: contract_billing_calculations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_calculations_status_index ON public.contract_billing_calculations USING btree (status);


--
-- Name: contract_billing_model_definitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_model_definitions_company_id_index ON public.contract_billing_model_definitions USING btree (company_id);


--
-- Name: contract_billing_model_definitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_model_definitions_company_id_is_active_index ON public.contract_billing_model_definitions USING btree (company_id, is_active);


--
-- Name: contract_billing_model_definitions_company_id_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_model_definitions_company_id_slug_index ON public.contract_billing_model_definitions USING btree (company_id, slug);


--
-- Name: contract_billing_model_definitions_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_model_definitions_company_id_sort_order_index ON public.contract_billing_model_definitions USING btree (company_id, sort_order);


--
-- Name: contract_billing_model_definitions_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_billing_model_definitions_slug_index ON public.contract_billing_model_definitions USING btree (slug);


--
-- Name: contract_clauses_company_id_category_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_clauses_company_id_category_status_index ON public.contract_clauses USING btree (company_id, category, status);


--
-- Name: contract_clauses_company_id_clause_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_clauses_company_id_clause_type_status_index ON public.contract_clauses USING btree (company_id, clause_type, status);


--
-- Name: contract_clauses_slug_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_clauses_slug_status_index ON public.contract_clauses USING btree (slug, status);


--
-- Name: contract_clauses_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_clauses_sort_order_index ON public.contract_clauses USING btree (sort_order);


--
-- Name: contract_comments_contract_id_is_internal_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_comments_contract_id_is_internal_index ON public.contract_comments USING btree (contract_id, is_internal);


--
-- Name: contract_comments_negotiation_id_is_resolved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_comments_negotiation_id_is_resolved_index ON public.contract_comments USING btree (negotiation_id, is_resolved);


--
-- Name: contract_comments_parent_id_thread_position_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_comments_parent_id_thread_position_index ON public.contract_comments USING btree (parent_id, thread_position);


--
-- Name: contract_comments_requires_response_response_due_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_comments_requires_response_response_due_index ON public.contract_comments USING btree (requires_response, response_due);


--
-- Name: contract_comments_version_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_comments_version_id_index ON public.contract_comments USING btree (version_id);


--
-- Name: contract_component_assignments_component_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_component_assignments_component_id_index ON public.contract_component_assignments USING btree (component_id);


--
-- Name: contract_component_assignments_contract_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_component_assignments_contract_id_status_index ON public.contract_component_assignments USING btree (contract_id, status);


--
-- Name: contract_components_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_components_company_id_category_index ON public.contract_components USING btree (company_id, category);


--
-- Name: contract_components_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_components_company_id_index ON public.contract_components USING btree (company_id);


--
-- Name: contract_components_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_components_company_id_status_index ON public.contract_components USING btree (company_id, status);


--
-- Name: contract_configurations_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_configurations_company_id_is_active_index ON public.contract_configurations USING btree (company_id, is_active);


--
-- Name: contract_configurations_company_id_version_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_configurations_company_id_version_index ON public.contract_configurations USING btree (company_id, version);


--
-- Name: contract_contact_assignments_access_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_access_level_index ON public.contract_contact_assignments USING btree (access_level);


--
-- Name: contract_contact_assignments_auto_assigned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_auto_assigned_index ON public.contract_contact_assignments USING btree (auto_assigned);


--
-- Name: contract_contact_assignments_billing_frequency_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_billing_frequency_status_index ON public.contract_contact_assignments USING btree (billing_frequency, status);


--
-- Name: contract_contact_assignments_company_id_contact_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_company_id_contact_id_index ON public.contract_contact_assignments USING btree (company_id, contact_id);


--
-- Name: contract_contact_assignments_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_company_id_contract_id_index ON public.contract_contact_assignments USING btree (company_id, contract_id);


--
-- Name: contract_contact_assignments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_company_id_index ON public.contract_contact_assignments USING btree (company_id);


--
-- Name: contract_contact_assignments_contact_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_contact_id_index ON public.contract_contact_assignments USING btree (contact_id);


--
-- Name: contract_contact_assignments_contact_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_contact_id_status_index ON public.contract_contact_assignments USING btree (contact_id, status);


--
-- Name: contract_contact_assignments_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_contract_id_index ON public.contract_contact_assignments USING btree (contract_id);


--
-- Name: contract_contact_assignments_contract_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_contract_id_status_index ON public.contract_contact_assignments USING btree (contract_id, status);


--
-- Name: contract_contact_assignments_last_access_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_last_access_date_index ON public.contract_contact_assignments USING btree (last_access_date);


--
-- Name: contract_contact_assignments_next_billing_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_next_billing_date_index ON public.contract_contact_assignments USING btree (next_billing_date);


--
-- Name: contract_contact_assignments_priority_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_priority_level_index ON public.contract_contact_assignments USING btree (priority_level);


--
-- Name: contract_contact_assignments_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_start_date_end_date_index ON public.contract_contact_assignments USING btree (start_date, end_date);


--
-- Name: contract_contact_assignments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_contact_assignments_status_index ON public.contract_contact_assignments USING btree (status);


--
-- Name: contract_dashboard_widgets_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_company_id_index ON public.contract_dashboard_widgets USING btree (company_id);


--
-- Name: contract_dashboard_widgets_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_company_id_is_active_index ON public.contract_dashboard_widgets USING btree (company_id, is_active);


--
-- Name: contract_dashboard_widgets_company_id_position_x_position_y_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_company_id_position_x_position_y_ind ON public.contract_dashboard_widgets USING btree (company_id, position_x, position_y);


--
-- Name: contract_dashboard_widgets_company_id_widget_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_company_id_widget_slug_index ON public.contract_dashboard_widgets USING btree (company_id, widget_slug);


--
-- Name: contract_dashboard_widgets_company_id_widget_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_company_id_widget_type_index ON public.contract_dashboard_widgets USING btree (company_id, widget_type);


--
-- Name: contract_dashboard_widgets_widget_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_dashboard_widgets_widget_slug_index ON public.contract_dashboard_widgets USING btree (widget_slug);


--
-- Name: contract_detail_configurations_company_id_contract_type_slug_in; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_detail_configurations_company_id_contract_type_slug_in ON public.contract_detail_configurations USING btree (company_id, contract_type_slug);


--
-- Name: contract_detail_configurations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_detail_configurations_company_id_index ON public.contract_detail_configurations USING btree (company_id);


--
-- Name: contract_detail_configurations_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_detail_configurations_company_id_is_active_index ON public.contract_detail_configurations USING btree (company_id, is_active);


--
-- Name: contract_detail_configurations_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_detail_configurations_contract_type_slug_index ON public.contract_detail_configurations USING btree (contract_type_slug);


--
-- Name: contract_field_definitions_company_id_field_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_company_id_field_slug_index ON public.contract_field_definitions USING btree (company_id, field_slug);


--
-- Name: contract_field_definitions_company_id_field_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_company_id_field_type_index ON public.contract_field_definitions USING btree (company_id, field_type);


--
-- Name: contract_field_definitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_company_id_index ON public.contract_field_definitions USING btree (company_id);


--
-- Name: contract_field_definitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_company_id_is_active_index ON public.contract_field_definitions USING btree (company_id, is_active);


--
-- Name: contract_field_definitions_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_company_id_sort_order_index ON public.contract_field_definitions USING btree (company_id, sort_order);


--
-- Name: contract_field_definitions_field_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_field_definitions_field_slug_index ON public.contract_field_definitions USING btree (field_slug);


--
-- Name: contract_form_sections_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_form_sections_company_id_index ON public.contract_form_sections USING btree (company_id);


--
-- Name: contract_form_sections_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_form_sections_company_id_is_active_index ON public.contract_form_sections USING btree (company_id, is_active);


--
-- Name: contract_form_sections_company_id_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_form_sections_company_id_section_slug_index ON public.contract_form_sections USING btree (company_id, section_slug);


--
-- Name: contract_form_sections_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_form_sections_company_id_sort_order_index ON public.contract_form_sections USING btree (company_id, sort_order);


--
-- Name: contract_form_sections_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_form_sections_section_slug_index ON public.contract_form_sections USING btree (section_slug);


--
-- Name: contract_invoice_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_invoice_company_id_contract_id_index ON public.contract_invoice USING btree (company_id, contract_id);


--
-- Name: contract_invoice_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_invoice_company_id_index ON public.contract_invoice USING btree (company_id);


--
-- Name: contract_invoice_company_id_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_invoice_company_id_invoice_id_index ON public.contract_invoice USING btree (company_id, invoice_id);


--
-- Name: contract_invoice_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_invoice_contract_id_index ON public.contract_invoice USING btree (contract_id);


--
-- Name: contract_invoice_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_invoice_invoice_id_index ON public.contract_invoice USING btree (invoice_id);


--
-- Name: contract_list_configurations_company_id_contract_type_slug_inde; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_list_configurations_company_id_contract_type_slug_inde ON public.contract_list_configurations USING btree (company_id, contract_type_slug);


--
-- Name: contract_list_configurations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_list_configurations_company_id_index ON public.contract_list_configurations USING btree (company_id);


--
-- Name: contract_list_configurations_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_list_configurations_company_id_is_active_index ON public.contract_list_configurations USING btree (company_id, is_active);


--
-- Name: contract_list_configurations_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_list_configurations_contract_type_slug_index ON public.contract_list_configurations USING btree (contract_type_slug);


--
-- Name: contract_menu_sections_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_menu_sections_company_id_index ON public.contract_menu_sections USING btree (company_id);


--
-- Name: contract_menu_sections_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_menu_sections_company_id_is_active_index ON public.contract_menu_sections USING btree (company_id, is_active);


--
-- Name: contract_menu_sections_company_id_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_menu_sections_company_id_section_slug_index ON public.contract_menu_sections USING btree (company_id, section_slug);


--
-- Name: contract_menu_sections_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_menu_sections_company_id_sort_order_index ON public.contract_menu_sections USING btree (company_id, sort_order);


--
-- Name: contract_menu_sections_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_menu_sections_section_slug_index ON public.contract_menu_sections USING btree (section_slug);


--
-- Name: contract_milestones_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_company_id_contract_id_index ON public.contract_milestones USING btree (company_id, contract_id);


--
-- Name: contract_milestones_company_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_company_id_due_date_index ON public.contract_milestones USING btree (company_id, due_date);


--
-- Name: contract_milestones_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_company_id_index ON public.contract_milestones USING btree (company_id);


--
-- Name: contract_milestones_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_company_id_status_index ON public.contract_milestones USING btree (company_id, status);


--
-- Name: contract_milestones_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_contract_id_index ON public.contract_milestones USING btree (contract_id);


--
-- Name: contract_milestones_contract_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_contract_id_sort_order_index ON public.contract_milestones USING btree (contract_id, sort_order);


--
-- Name: contract_milestones_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_invoice_id_index ON public.contract_milestones USING btree (invoice_id);


--
-- Name: contract_milestones_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_milestones_status_index ON public.contract_milestones USING btree (status);


--
-- Name: contract_navigation_items_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_company_id_index ON public.contract_navigation_items USING btree (company_id);


--
-- Name: contract_navigation_items_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_company_id_is_active_index ON public.contract_navigation_items USING btree (company_id, is_active);


--
-- Name: contract_navigation_items_company_id_parent_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_company_id_parent_slug_index ON public.contract_navigation_items USING btree (company_id, parent_slug);


--
-- Name: contract_navigation_items_company_id_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_company_id_slug_index ON public.contract_navigation_items USING btree (company_id, slug);


--
-- Name: contract_navigation_items_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_company_id_sort_order_index ON public.contract_navigation_items USING btree (company_id, sort_order);


--
-- Name: contract_navigation_items_parent_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_parent_slug_index ON public.contract_navigation_items USING btree (parent_slug);


--
-- Name: contract_navigation_items_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_navigation_items_slug_index ON public.contract_navigation_items USING btree (slug);


--
-- Name: contract_negotiations_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_negotiations_client_id_status_index ON public.contract_negotiations USING btree (client_id, status);


--
-- Name: contract_negotiations_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_negotiations_company_id_status_index ON public.contract_negotiations USING btree (company_id, status);


--
-- Name: contract_negotiations_created_by_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_negotiations_created_by_assigned_to_index ON public.contract_negotiations USING btree (created_by, assigned_to);


--
-- Name: contract_negotiations_status_phase_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_negotiations_status_phase_index ON public.contract_negotiations USING btree (status, phase);


--
-- Name: contract_schedules_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_archived_at_index ON public.contract_schedules USING btree (archived_at);


--
-- Name: contract_schedules_auto_assign_assets_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_auto_assign_assets_index ON public.contract_schedules USING btree (auto_assign_assets);


--
-- Name: contract_schedules_company_id_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_company_id_archived_at_index ON public.contract_schedules USING btree (company_id, archived_at);


--
-- Name: contract_schedules_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_company_id_contract_id_index ON public.contract_schedules USING btree (company_id, contract_id);


--
-- Name: contract_schedules_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_company_id_index ON public.contract_schedules USING btree (company_id);


--
-- Name: contract_schedules_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_contract_id_index ON public.contract_schedules USING btree (contract_id);


--
-- Name: contract_schedules_contract_id_schedule_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_contract_id_schedule_type_index ON public.contract_schedules USING btree (contract_id, schedule_type);


--
-- Name: contract_schedules_effective_date_expiration_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_effective_date_expiration_date_index ON public.contract_schedules USING btree (effective_date, expiration_date);


--
-- Name: contract_schedules_is_template_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_is_template_index ON public.contract_schedules USING btree (is_template);


--
-- Name: contract_schedules_next_review_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_next_review_date_index ON public.contract_schedules USING btree (next_review_date);


--
-- Name: contract_schedules_schedule_letter_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_schedule_letter_index ON public.contract_schedules USING btree (schedule_letter);


--
-- Name: contract_schedules_schedule_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_schedule_type_status_index ON public.contract_schedules USING btree (schedule_type, status);


--
-- Name: contract_schedules_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_schedules_status_index ON public.contract_schedules USING btree (status);


--
-- Name: contract_signatures_company_id_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_company_id_contract_id_index ON public.contract_signatures USING btree (company_id, contract_id);


--
-- Name: contract_signatures_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_company_id_index ON public.contract_signatures USING btree (company_id);


--
-- Name: contract_signatures_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_company_id_status_index ON public.contract_signatures USING btree (company_id, status);


--
-- Name: contract_signatures_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_contract_id_index ON public.contract_signatures USING btree (contract_id);


--
-- Name: contract_signatures_contract_id_signer_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_contract_id_signer_type_index ON public.contract_signatures USING btree (contract_id, signer_type);


--
-- Name: contract_signatures_contract_id_signing_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_contract_id_signing_order_index ON public.contract_signatures USING btree (contract_id, signing_order);


--
-- Name: contract_signatures_contract_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_contract_id_status_index ON public.contract_signatures USING btree (contract_id, status);


--
-- Name: contract_signatures_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_signatures_status_index ON public.contract_signatures USING btree (status);


--
-- Name: contract_status_definitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_index ON public.contract_status_definitions USING btree (company_id);


--
-- Name: contract_status_definitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_is_active_index ON public.contract_status_definitions USING btree (company_id, is_active);


--
-- Name: contract_status_definitions_company_id_is_final_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_is_final_index ON public.contract_status_definitions USING btree (company_id, is_final);


--
-- Name: contract_status_definitions_company_id_is_initial_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_is_initial_index ON public.contract_status_definitions USING btree (company_id, is_initial);


--
-- Name: contract_status_definitions_company_id_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_slug_index ON public.contract_status_definitions USING btree (company_id, slug);


--
-- Name: contract_status_definitions_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_company_id_sort_order_index ON public.contract_status_definitions USING btree (company_id, sort_order);


--
-- Name: contract_status_definitions_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_definitions_slug_index ON public.contract_status_definitions USING btree (slug);


--
-- Name: contract_status_transitions_company_id_from_status_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_company_id_from_status_slug_index ON public.contract_status_transitions USING btree (company_id, from_status_slug);


--
-- Name: contract_status_transitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_company_id_index ON public.contract_status_transitions USING btree (company_id);


--
-- Name: contract_status_transitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_company_id_is_active_index ON public.contract_status_transitions USING btree (company_id, is_active);


--
-- Name: contract_status_transitions_company_id_to_status_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_company_id_to_status_slug_index ON public.contract_status_transitions USING btree (company_id, to_status_slug);


--
-- Name: contract_status_transitions_from_status_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_from_status_slug_index ON public.contract_status_transitions USING btree (from_status_slug);


--
-- Name: contract_status_transitions_to_status_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_status_transitions_to_status_slug_index ON public.contract_status_transitions USING btree (to_status_slug);


--
-- Name: contract_template_clauses_clause_id_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_template_clauses_clause_id_template_id_index ON public.contract_template_clauses USING btree (clause_id, template_id);


--
-- Name: contract_template_clauses_template_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_template_clauses_template_id_sort_order_index ON public.contract_template_clauses USING btree (template_id, sort_order);


--
-- Name: contract_templates_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_archived_at_index ON public.contract_templates USING btree (archived_at);


--
-- Name: contract_templates_company_id_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_company_id_archived_at_index ON public.contract_templates USING btree (company_id, archived_at);


--
-- Name: contract_templates_company_id_billing_model_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_company_id_billing_model_index ON public.contract_templates USING btree (company_id, billing_model);


--
-- Name: contract_templates_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_company_id_index ON public.contract_templates USING btree (company_id);


--
-- Name: contract_templates_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_company_id_status_index ON public.contract_templates USING btree (company_id, status);


--
-- Name: contract_templates_company_id_template_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_company_id_template_type_index ON public.contract_templates USING btree (company_id, template_type);


--
-- Name: contract_templates_next_review_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_next_review_date_index ON public.contract_templates USING btree (next_review_date);


--
-- Name: contract_templates_parent_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_parent_template_id_index ON public.contract_templates USING btree (parent_template_id);


--
-- Name: contract_templates_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_slug_index ON public.contract_templates USING btree (slug);


--
-- Name: contract_templates_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_status_index ON public.contract_templates USING btree (status);


--
-- Name: contract_templates_template_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_templates_template_type_status_index ON public.contract_templates USING btree (template_type, status);


--
-- Name: contract_type_definitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_definitions_company_id_index ON public.contract_type_definitions USING btree (company_id);


--
-- Name: contract_type_definitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_definitions_company_id_is_active_index ON public.contract_type_definitions USING btree (company_id, is_active);


--
-- Name: contract_type_definitions_company_id_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_definitions_company_id_slug_index ON public.contract_type_definitions USING btree (company_id, slug);


--
-- Name: contract_type_definitions_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_definitions_company_id_sort_order_index ON public.contract_type_definitions USING btree (company_id, sort_order);


--
-- Name: contract_type_definitions_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_definitions_slug_index ON public.contract_type_definitions USING btree (slug);


--
-- Name: contract_type_form_mappings_company_id_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_company_id_contract_type_slug_index ON public.contract_type_form_mappings USING btree (company_id, contract_type_slug);


--
-- Name: contract_type_form_mappings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_company_id_index ON public.contract_type_form_mappings USING btree (company_id);


--
-- Name: contract_type_form_mappings_company_id_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_company_id_section_slug_index ON public.contract_type_form_mappings USING btree (company_id, section_slug);


--
-- Name: contract_type_form_mappings_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_company_id_sort_order_index ON public.contract_type_form_mappings USING btree (company_id, sort_order);


--
-- Name: contract_type_form_mappings_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_contract_type_slug_index ON public.contract_type_form_mappings USING btree (contract_type_slug);


--
-- Name: contract_type_form_mappings_section_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_type_form_mappings_section_slug_index ON public.contract_type_form_mappings USING btree (section_slug);


--
-- Name: contract_versions_contract_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_versions_contract_id_status_index ON public.contract_versions USING btree (contract_id, status);


--
-- Name: contract_versions_contract_id_version_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_versions_contract_id_version_number_index ON public.contract_versions USING btree (contract_id, version_number);


--
-- Name: contract_versions_negotiation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_versions_negotiation_id_index ON public.contract_versions USING btree (negotiation_id);


--
-- Name: contract_view_definitions_company_id_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_company_id_contract_type_slug_index ON public.contract_view_definitions USING btree (company_id, contract_type_slug);


--
-- Name: contract_view_definitions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_company_id_index ON public.contract_view_definitions USING btree (company_id);


--
-- Name: contract_view_definitions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_company_id_is_active_index ON public.contract_view_definitions USING btree (company_id, is_active);


--
-- Name: contract_view_definitions_company_id_view_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_company_id_view_type_index ON public.contract_view_definitions USING btree (company_id, view_type);


--
-- Name: contract_view_definitions_contract_type_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_contract_type_slug_index ON public.contract_view_definitions USING btree (contract_type_slug);


--
-- Name: contract_view_definitions_view_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contract_view_definitions_view_type_index ON public.contract_view_definitions USING btree (view_type);


--
-- Name: contracts_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_archived_at_index ON public.contracts USING btree (archived_at);


--
-- Name: contracts_auto_calculate_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_auto_calculate_idx ON public.contracts USING btree (auto_calculate_billing);


--
-- Name: contracts_billing_model_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_billing_model_idx ON public.contracts USING btree (billing_model);


--
-- Name: contracts_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_client_id_index ON public.contracts USING btree (client_id);


--
-- Name: contracts_company_id_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_company_id_archived_at_index ON public.contracts USING btree (company_id, archived_at);


--
-- Name: contracts_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_company_id_client_id_index ON public.contracts USING btree (company_id, client_id);


--
-- Name: contracts_company_id_contract_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_company_id_contract_type_index ON public.contracts USING btree (company_id, contract_type);


--
-- Name: contracts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_company_id_index ON public.contracts USING btree (company_id);


--
-- Name: contracts_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_company_id_status_index ON public.contracts USING btree (company_id, status);


--
-- Name: contracts_contract_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_contract_template_id_index ON public.contracts USING btree (contract_template_id);


--
-- Name: contracts_is_programmable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_is_programmable_index ON public.contracts USING btree (is_programmable);


--
-- Name: contracts_next_billing_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_next_billing_idx ON public.contracts USING btree (next_billing_date);


--
-- Name: contracts_programmable_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_programmable_status_idx ON public.contracts USING btree (is_programmable, status);


--
-- Name: contracts_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_project_id_index ON public.contracts USING btree (project_id);


--
-- Name: contracts_quote_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_quote_id_index ON public.contracts USING btree (quote_id);


--
-- Name: contracts_signature_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_signature_status_index ON public.contracts USING btree (signature_status);


--
-- Name: contracts_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_start_date_end_date_index ON public.contracts USING btree (start_date, end_date);


--
-- Name: contracts_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_status_index ON public.contracts USING btree (status);


--
-- Name: contracts_template_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_template_id_idx ON public.contracts USING btree (contract_template_id);


--
-- Name: contracts_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contracts_template_id_index ON public.contracts USING btree (template_id);


--
-- Name: conversion_events_attributed_campaign_id_converted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversion_events_attributed_campaign_id_converted_at_index ON public.conversion_events USING btree (attributed_campaign_id, converted_at);


--
-- Name: conversion_events_company_id_converted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversion_events_company_id_converted_at_index ON public.conversion_events USING btree (company_id, converted_at);


--
-- Name: conversion_events_event_type_converted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversion_events_event_type_converted_at_index ON public.conversion_events USING btree (event_type, converted_at);


--
-- Name: cross_company_access_type_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_access_type_idx ON public.cross_company_users USING btree (company_id, access_type);


--
-- Name: cross_company_expires_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_expires_idx ON public.cross_company_users USING btree (access_expires_at);


--
-- Name: cross_company_primary_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_primary_idx ON public.cross_company_users USING btree (primary_company_id, user_id);


--
-- Name: cross_company_user_active_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_user_active_idx ON public.cross_company_users USING btree (user_id, is_active);


--
-- Name: cross_company_users_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_users_company_id_index ON public.cross_company_users USING btree (company_id);


--
-- Name: cross_company_users_primary_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_users_primary_company_id_index ON public.cross_company_users USING btree (primary_company_id);


--
-- Name: cross_company_users_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_company_users_user_id_index ON public.cross_company_users USING btree (user_id);


--
-- Name: custom_quick_actions_company_id_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_quick_actions_company_id_user_id_is_active_index ON public.custom_quick_actions USING btree (company_id, user_id, is_active);


--
-- Name: custom_quick_actions_company_id_visibility_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_quick_actions_company_id_visibility_is_active_index ON public.custom_quick_actions USING btree (company_id, visibility, is_active);


--
-- Name: dashboard_activity_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dashboard_activity_logs_user_id_created_at_index ON public.dashboard_activity_logs USING btree (user_id, created_at);


--
-- Name: dashboard_metrics_company_id_metric_key_calculated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dashboard_metrics_company_id_metric_key_calculated_at_index ON public.dashboard_metrics USING btree (company_id, metric_key, calculated_at);


--
-- Name: dashboard_presets_company_id_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dashboard_presets_company_id_role_index ON public.dashboard_presets USING btree (company_id, role);


--
-- Name: docs_category_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX docs_category_idx ON public.documents USING btree (category);


--
-- Name: docs_company_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX docs_company_idx ON public.documents USING btree (company_id);


--
-- Name: docs_morph_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX docs_morph_idx ON public.documents USING btree (documentable_type, documentable_id);


--
-- Name: docs_private_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX docs_private_idx ON public.documents USING btree (is_private);


--
-- Name: docs_uploader_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX docs_uploader_idx ON public.documents USING btree (uploaded_by);


--
-- Name: dunning_actions_action_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_action_type_status_index ON public.dunning_actions USING btree (action_type, status);


--
-- Name: dunning_actions_campaign_id_sequence_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_campaign_id_sequence_id_index ON public.dunning_actions USING btree (campaign_id, sequence_id);


--
-- Name: dunning_actions_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_client_id_status_index ON public.dunning_actions USING btree (client_id, status);


--
-- Name: dunning_actions_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_company_id_status_index ON public.dunning_actions USING btree (company_id, status);


--
-- Name: dunning_actions_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_invoice_id_index ON public.dunning_actions USING btree (invoice_id);


--
-- Name: dunning_actions_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dunning_actions_scheduled_at_index ON public.dunning_actions USING btree (scheduled_at);


--
-- Name: email_accounts_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_accounts_company_id_user_id_index ON public.email_accounts USING btree (company_id, user_id);


--
-- Name: email_accounts_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_accounts_user_id_is_active_index ON public.email_accounts USING btree (user_id, is_active);


--
-- Name: email_attachments_content_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_attachments_content_type_index ON public.email_attachments USING btree (content_type);


--
-- Name: email_attachments_email_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_attachments_email_message_id_index ON public.email_attachments USING btree (email_message_id);


--
-- Name: email_attachments_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_attachments_hash_index ON public.email_attachments USING btree (hash);


--
-- Name: email_folders_email_account_id_remote_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_folders_email_account_id_remote_id_index ON public.email_folders USING btree (email_account_id, remote_id);


--
-- Name: email_folders_email_account_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_folders_email_account_id_type_index ON public.email_folders USING btree (email_account_id, type);


--
-- Name: email_messages_email_account_id_email_folder_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_email_account_id_email_folder_id_index ON public.email_messages USING btree (email_account_id, email_folder_id);


--
-- Name: email_messages_email_account_id_remote_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_email_account_id_remote_id_index ON public.email_messages USING btree (email_account_id, remote_id);


--
-- Name: email_messages_is_read_received_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_is_read_received_at_index ON public.email_messages USING btree (is_read, received_at);


--
-- Name: email_messages_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_message_id_index ON public.email_messages USING btree (message_id);


--
-- Name: email_messages_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_sent_at_index ON public.email_messages USING btree (sent_at);


--
-- Name: email_messages_subject_body_text_from_name_from_address_fulltex; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_subject_body_text_from_name_from_address_fulltex ON public.email_messages USING gin (((((to_tsvector('english'::regconfig, (subject)::text) || to_tsvector('english'::regconfig, body_text)) || to_tsvector('english'::regconfig, (from_name)::text)) || to_tsvector('english'::regconfig, from_address))));


--
-- Name: email_messages_thread_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_thread_id_index ON public.email_messages USING btree (thread_id);


--
-- Name: email_messages_uid_email_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_messages_uid_email_account_id_index ON public.email_messages USING btree (uid, email_account_id);


--
-- Name: email_signatures_email_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_signatures_email_account_id_index ON public.email_signatures USING btree (email_account_id);


--
-- Name: email_signatures_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_signatures_user_id_index ON public.email_signatures USING btree (user_id);


--
-- Name: email_templates_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_templates_company_id_category_index ON public.email_templates USING btree (company_id, category);


--
-- Name: email_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_templates_company_id_is_active_index ON public.email_templates USING btree (company_id, is_active);


--
-- Name: email_tracking_campaign_id_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_tracking_campaign_id_sent_at_index ON public.email_tracking USING btree (campaign_id, sent_at);


--
-- Name: email_tracking_contact_id_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_tracking_contact_id_sent_at_index ON public.email_tracking USING btree (contact_id, sent_at);


--
-- Name: email_tracking_lead_id_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_tracking_lead_id_sent_at_index ON public.email_tracking USING btree (lead_id, sent_at);


--
-- Name: email_tracking_recipient_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_tracking_recipient_email_index ON public.email_tracking USING btree (recipient_email);


--
-- Name: email_tracking_tracking_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_tracking_tracking_id_index ON public.email_tracking USING btree (tracking_id);


--
-- Name: employee_schedules_company_id_user_id_scheduled_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_schedules_company_id_user_id_scheduled_date_index ON public.employee_schedules USING btree (company_id, user_id, scheduled_date);


--
-- Name: employee_time_entries_company_id_pay_period_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_company_id_pay_period_id_index ON public.employee_time_entries USING btree (company_id, pay_period_id);


--
-- Name: employee_time_entries_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_company_id_status_index ON public.employee_time_entries USING btree (company_id, status);


--
-- Name: employee_time_entries_company_id_user_id_clock_in_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_company_id_user_id_clock_in_index ON public.employee_time_entries USING btree (company_id, user_id, clock_in);


--
-- Name: employee_time_entries_exported_to_payroll_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_exported_to_payroll_index ON public.employee_time_entries USING btree (exported_to_payroll);


--
-- Name: employee_time_entries_user_id_clock_in_clock_out_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_user_id_clock_in_clock_out_index ON public.employee_time_entries USING btree (user_id, clock_in, clock_out);


--
-- Name: employee_time_entries_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employee_time_entries_user_id_status_index ON public.employee_time_entries USING btree (user_id, status);


--
-- Name: expenses_company_id_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_company_id_category_id_index ON public.expenses USING btree (company_id, category_id);


--
-- Name: expenses_company_id_expense_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_company_id_expense_date_index ON public.expenses USING btree (company_id, expense_date);


--
-- Name: expenses_company_id_is_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_company_id_is_billable_index ON public.expenses USING btree (company_id, is_billable);


--
-- Name: expenses_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_company_id_user_id_index ON public.expenses USING btree (company_id, user_id);


--
-- Name: files_company_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_company_idx ON public.files USING btree (company_id);


--
-- Name: files_morph_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_morph_idx ON public.files USING btree (fileable_type, fileable_id);


--
-- Name: files_public_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_public_idx ON public.files USING btree (is_public);


--
-- Name: files_type_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_type_idx ON public.files USING btree (file_type);


--
-- Name: files_uploader_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_uploader_idx ON public.files USING btree (uploaded_by);


--
-- Name: hierarchy_ancestor_depth_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hierarchy_ancestor_depth_idx ON public.company_hierarchies USING btree (ancestor_id, depth);


--
-- Name: hierarchy_descendant_depth_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hierarchy_descendant_depth_idx ON public.company_hierarchies USING btree (descendant_id, depth);


--
-- Name: hierarchy_path_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hierarchy_path_idx ON public.company_hierarchies USING btree (path);


--
-- Name: hr_settings_override_lookup; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hr_settings_override_lookup ON public.hr_settings_overrides USING btree (company_id, overridable_type, overridable_id);


--
-- Name: idx_active_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_active_status ON public.device_mappings USING btree (is_active);


--
-- Name: idx_asset; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_asset ON public.device_mappings USING btree (asset_id);


--
-- Name: idx_client; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_client ON public.device_mappings USING btree (client_id);


--
-- Name: idx_client_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_client_active ON public.device_mappings USING btree (client_id, is_active);


--
-- Name: idx_client_portal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_client_portal ON public.contacts USING btree (company_id, client_id, has_portal_access);


--
-- Name: idx_company_invitation_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_company_invitation_status ON public.contacts USING btree (company_id, invitation_status);


--
-- Name: idx_integration_rmm_device; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_integration_rmm_device ON public.device_mappings USING btree (integration_id, rmm_device_id);


--
-- Name: idx_invitation_status_expires; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invitation_status_expires ON public.contacts USING btree (invitation_status, invitation_expires_at);


--
-- Name: idx_invitation_token; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invitation_token ON public.contacts USING btree (invitation_token);


--
-- Name: idx_last_updated; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_last_updated ON public.device_mappings USING btree (last_updated);


--
-- Name: idx_portal_access; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_portal_access ON public.contacts USING btree (company_id, email, has_portal_access);


--
-- Name: idx_rmm_integrations_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_rmm_integrations_active ON public.rmm_integrations USING btree (is_active);


--
-- Name: idx_rmm_integrations_company; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_rmm_integrations_company ON public.rmm_integrations USING btree (company_id);


--
-- Name: idx_rmm_integrations_sync; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_rmm_integrations_sync ON public.rmm_integrations USING btree (last_sync_at);


--
-- Name: idx_rmm_integrations_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_rmm_integrations_type ON public.rmm_integrations USING btree (rmm_type);


--
-- Name: idx_ticket_replies_sentiment_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_replies_sentiment_created ON public.ticket_replies USING btree (sentiment_label, created_at);


--
-- Name: idx_ticket_replies_sentiment_ticket; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_replies_sentiment_ticket ON public.ticket_replies USING btree (sentiment_score, ticket_id);


--
-- Name: idx_ticket_watchers_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_active ON public.ticket_watchers USING btree (is_active);


--
-- Name: idx_ticket_watchers_added_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_added_by ON public.ticket_watchers USING btree (added_by);


--
-- Name: idx_ticket_watchers_company; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_company ON public.ticket_watchers USING btree (company_id);


--
-- Name: idx_ticket_watchers_company_ticket; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_company_ticket ON public.ticket_watchers USING btree (company_id, ticket_id);


--
-- Name: idx_ticket_watchers_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_email ON public.ticket_watchers USING btree (email);


--
-- Name: idx_ticket_watchers_ticket; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_ticket ON public.ticket_watchers USING btree (ticket_id);


--
-- Name: idx_ticket_watchers_ticket_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_ticket_active ON public.ticket_watchers USING btree (ticket_id, is_active);


--
-- Name: idx_ticket_watchers_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ticket_watchers_user ON public.ticket_watchers USING btree (user_id);


--
-- Name: idx_tickets_assigned_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_assigned_status ON public.tickets USING btree (assigned_to, status);


--
-- Name: idx_tickets_client_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_client_status ON public.tickets USING btree (client_id, status);


--
-- Name: idx_tickets_company_assigned_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_company_assigned_status ON public.tickets USING btree (company_id, assigned_to, status);


--
-- Name: idx_tickets_company_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_company_created ON public.tickets USING btree (company_id, created_at);


--
-- Name: idx_tickets_company_priority_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_company_priority_status ON public.tickets USING btree (company_id, priority, status);


--
-- Name: idx_tickets_company_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_company_status ON public.tickets USING btree (company_id, status);


--
-- Name: idx_tickets_created_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_created_status ON public.tickets USING btree (created_at, status);


--
-- Name: idx_tickets_resolved; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_resolved ON public.tickets USING btree (is_resolved, resolved_at);


--
-- Name: idx_tickets_sentiment_company; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_sentiment_company ON public.tickets USING btree (sentiment_score, company_id);


--
-- Name: idx_tickets_sentiment_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tickets_sentiment_created ON public.tickets USING btree (sentiment_label, created_at);


--
-- Name: in_app_notifications_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX in_app_notifications_user_id_created_at_index ON public.in_app_notifications USING btree (user_id, created_at);


--
-- Name: in_app_notifications_user_id_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX in_app_notifications_user_id_is_read_index ON public.in_app_notifications USING btree (user_id, is_read);


--
-- Name: invoice_items_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_archived_at_index ON public.invoice_items USING btree (archived_at);


--
-- Name: invoice_items_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_company_id_index ON public.invoice_items USING btree (company_id);


--
-- Name: invoice_items_company_id_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_company_id_invoice_id_index ON public.invoice_items USING btree (company_id, invoice_id);


--
-- Name: invoice_items_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_invoice_id_index ON public.invoice_items USING btree (invoice_id);


--
-- Name: invoice_items_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_order_index ON public.invoice_items USING btree ("order");


--
-- Name: invoice_items_service_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_service_type_index ON public.invoice_items USING btree (service_type);


--
-- Name: invoice_items_tax_jurisdiction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_items_tax_jurisdiction_id_index ON public.invoice_items USING btree (tax_jurisdiction_id);


--
-- Name: invoices_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_archived_at_index ON public.invoices USING btree (archived_at);


--
-- Name: invoices_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_client_id_index ON public.invoices USING btree (client_id);


--
-- Name: invoices_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_client_id_status_index ON public.invoices USING btree (client_id, status);


--
-- Name: invoices_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_company_id_client_id_index ON public.invoices USING btree (company_id, client_id);


--
-- Name: invoices_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_company_id_index ON public.invoices USING btree (company_id);


--
-- Name: invoices_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_contract_id_index ON public.invoices USING btree (contract_id);


--
-- Name: invoices_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_date_index ON public.invoices USING btree (date);


--
-- Name: invoices_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_due_date_index ON public.invoices USING btree (due_date);


--
-- Name: invoices_is_recurring_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_is_recurring_index ON public.invoices USING btree (is_recurring);


--
-- Name: invoices_next_recurring_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_next_recurring_date_index ON public.invoices USING btree (next_recurring_date);


--
-- Name: invoices_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_number_index ON public.invoices USING btree (number);


--
-- Name: invoices_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_project_id_index ON public.invoices USING btree (project_id);


--
-- Name: invoices_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_status_index ON public.invoices USING btree (status);


--
-- Name: invoices_url_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_url_key_index ON public.invoices USING btree (url_key);


--
-- Name: ip_lookup_logs_cached_until_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_cached_until_index ON public.ip_lookup_logs USING btree (cached_until);


--
-- Name: ip_lookup_logs_company_id_threat_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_company_id_threat_level_index ON public.ip_lookup_logs USING btree (company_id, threat_level);


--
-- Name: ip_lookup_logs_country_code_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_country_code_company_id_index ON public.ip_lookup_logs USING btree (country_code, company_id);


--
-- Name: ip_lookup_logs_country_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_country_code_index ON public.ip_lookup_logs USING btree (country_code);


--
-- Name: ip_lookup_logs_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_ip_address_index ON public.ip_lookup_logs USING btree (ip_address);


--
-- Name: ip_lookup_logs_is_proxy_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_is_proxy_index ON public.ip_lookup_logs USING btree (is_proxy);


--
-- Name: ip_lookup_logs_is_tor_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_is_tor_index ON public.ip_lookup_logs USING btree (is_tor);


--
-- Name: ip_lookup_logs_is_vpn_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_is_vpn_index ON public.ip_lookup_logs USING btree (is_vpn);


--
-- Name: ip_lookup_logs_is_vpn_is_proxy_is_tor_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_is_vpn_is_proxy_is_tor_index ON public.ip_lookup_logs USING btree (is_vpn, is_proxy, is_tor);


--
-- Name: ip_lookup_logs_threat_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_lookup_logs_threat_level_index ON public.ip_lookup_logs USING btree (threat_level);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: kpi_targets_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kpi_targets_company_id_is_active_index ON public.kpi_targets USING btree (company_id, is_active);


--
-- Name: kpi_targets_company_id_kpi_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kpi_targets_company_id_kpi_name_index ON public.kpi_targets USING btree (company_id, kpi_name);


--
-- Name: kpi_targets_company_id_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kpi_targets_company_id_period_index ON public.kpi_targets USING btree (company_id, period);


--
-- Name: lead_activities_lead_id_activity_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lead_activities_lead_id_activity_date_index ON public.lead_activities USING btree (lead_id, activity_date);


--
-- Name: lead_activities_type_activity_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lead_activities_type_activity_date_index ON public.lead_activities USING btree (type, activity_date);


--
-- Name: lead_sources_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lead_sources_company_id_is_active_index ON public.lead_sources USING btree (company_id, is_active);


--
-- Name: leads_assigned_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX leads_assigned_user_id_status_index ON public.leads USING btree (assigned_user_id, status);


--
-- Name: leads_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX leads_company_id_status_index ON public.leads USING btree (company_id, status);


--
-- Name: leads_company_id_total_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX leads_company_id_total_score_index ON public.leads USING btree (company_id, total_score);


--
-- Name: leads_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX leads_email_index ON public.leads USING btree (email);


--
-- Name: locations_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_archived_at_index ON public.locations USING btree (archived_at);


--
-- Name: locations_client_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_address_index ON public.locations USING btree (client_id, address);


--
-- Name: locations_client_filter_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_filter_index ON public.locations USING btree (client_id, state, country);


--
-- Name: locations_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_id_index ON public.locations USING btree (client_id);


--
-- Name: locations_client_id_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_id_primary_index ON public.locations USING btree (client_id, "primary");


--
-- Name: locations_client_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_order_index ON public.locations USING btree (client_id, "primary", name);


--
-- Name: locations_client_search_city_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_search_city_index ON public.locations USING btree (client_id, city);


--
-- Name: locations_client_search_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_search_name_index ON public.locations USING btree (client_id, name);


--
-- Name: locations_client_search_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_client_search_state_index ON public.locations USING btree (client_id, state);


--
-- Name: locations_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_company_id_client_id_index ON public.locations USING btree (company_id, client_id);


--
-- Name: locations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_company_id_index ON public.locations USING btree (company_id);


--
-- Name: locations_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_name_index ON public.locations USING btree (name);


--
-- Name: locations_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX locations_primary_index ON public.locations USING btree ("primary");


--
-- Name: mail_queue_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_category_index ON public.mail_queue USING btree (category);


--
-- Name: mail_queue_company_id_status_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_company_id_status_scheduled_at_index ON public.mail_queue USING btree (company_id, status, scheduled_at);


--
-- Name: mail_queue_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_created_at_index ON public.mail_queue USING btree (created_at);


--
-- Name: mail_queue_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_message_id_index ON public.mail_queue USING btree (message_id);


--
-- Name: mail_queue_related_type_related_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_related_type_related_id_index ON public.mail_queue USING btree (related_type, related_id);


--
-- Name: mail_queue_status_attempts_next_retry_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_status_attempts_next_retry_at_index ON public.mail_queue USING btree (status, attempts, next_retry_at);


--
-- Name: mail_queue_status_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_status_scheduled_at_index ON public.mail_queue USING btree (status, scheduled_at);


--
-- Name: mail_queue_to_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_to_email_index ON public.mail_queue USING btree (to_email);


--
-- Name: mail_queue_tracking_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_tracking_token_index ON public.mail_queue USING btree (tracking_token);


--
-- Name: mail_queue_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_queue_uuid_index ON public.mail_queue USING btree (uuid);


--
-- Name: mail_templates_company_id_category_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_templates_company_id_category_is_active_index ON public.mail_templates USING btree (company_id, category, is_active);


--
-- Name: mail_templates_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_templates_name_index ON public.mail_templates USING btree (name);


--
-- Name: marketing_campaigns_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaigns_company_id_status_index ON public.marketing_campaigns USING btree (company_id, status);


--
-- Name: marketing_campaigns_status_start_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaigns_status_start_date_index ON public.marketing_campaigns USING btree (status, start_date);


--
-- Name: media_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);


--
-- Name: media_order_column_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_order_column_index ON public.media USING btree (order_column);


--
-- Name: networks_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_archived_at_index ON public.networks USING btree (archived_at);


--
-- Name: networks_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_client_id_index ON public.networks USING btree (client_id);


--
-- Name: networks_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_company_id_client_id_index ON public.networks USING btree (company_id, client_id);


--
-- Name: networks_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_company_id_index ON public.networks USING btree (company_id);


--
-- Name: networks_location_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_location_id_index ON public.networks USING btree (location_id);


--
-- Name: networks_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_name_index ON public.networks USING btree (name);


--
-- Name: networks_vlan_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX networks_vlan_index ON public.networks USING btree (vlan);


--
-- Name: notification_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_logs_created_at_index ON public.notification_logs USING btree (created_at);


--
-- Name: notification_logs_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_logs_notifiable_type_notifiable_id_index ON public.notification_logs USING btree (notifiable_type, notifiable_id);


--
-- Name: notification_logs_notification_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_logs_notification_type_index ON public.notification_logs USING btree (notification_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: oauth_states_state_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_states_state_expires_at_index ON public.oauth_states USING btree (state, expires_at);


--
-- Name: pay_periods_company_id_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pay_periods_company_id_start_date_end_date_index ON public.pay_periods USING btree (company_id, start_date, end_date);


--
-- Name: pay_periods_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pay_periods_company_id_status_index ON public.pay_periods USING btree (company_id, status);


--
-- Name: payment_applications_applicable_type_applicable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_applications_applicable_type_applicable_id_index ON public.payment_applications USING btree (applicable_type, applicable_id);


--
-- Name: payment_applications_company_id_applied_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_applications_company_id_applied_date_index ON public.payment_applications USING btree (company_id, applied_date);


--
-- Name: payment_applications_payment_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_applications_payment_id_is_active_index ON public.payment_applications USING btree (payment_id, is_active);


--
-- Name: payment_methods_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_company_id_client_id_index ON public.payment_methods USING btree (company_id, client_id);


--
-- Name: payment_methods_company_id_is_active_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_company_id_is_active_is_default_index ON public.payment_methods USING btree (company_id, is_active, is_default);


--
-- Name: payment_methods_fingerprint_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_fingerprint_index ON public.payment_methods USING btree (fingerprint);


--
-- Name: payment_methods_provider_customer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_provider_customer_id_index ON public.payment_methods USING btree (provider_customer_id);


--
-- Name: payment_methods_provider_payment_method_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_provider_payment_method_id_index ON public.payment_methods USING btree (provider_payment_method_id);


--
-- Name: payments_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_client_id_status_index ON public.payments USING btree (client_id, status);


--
-- Name: payments_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_company_id_status_index ON public.payments USING btree (company_id, status);


--
-- Name: payments_gateway_gateway_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_gateway_gateway_transaction_id_index ON public.payments USING btree (gateway, gateway_transaction_id);


--
-- Name: payments_payment_date_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_payment_date_company_id_index ON public.payments USING btree (payment_date, company_id);


--
-- Name: permissions_active_expires_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_active_expires_idx ON public.subsidiary_permissions USING btree (is_active, expires_at);


--
-- Name: permissions_entity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_entity_index ON public.bouncer_permissions USING btree (entity_id, entity_type, scope);


--
-- Name: permissions_grantee_user_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_grantee_user_idx ON public.subsidiary_permissions USING btree (grantee_company_id, user_id);


--
-- Name: permissions_granter_resource_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_granter_resource_idx ON public.subsidiary_permissions USING btree (granter_company_id, resource_type);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: physical_mail_bank_accounts_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_bank_accounts_company_id_is_active_index ON public.physical_mail_bank_accounts USING btree (company_id, is_active);


--
-- Name: physical_mail_cheques_check_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_cheques_check_number_index ON public.physical_mail_cheques USING btree (check_number);


--
-- Name: physical_mail_cheques_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_cheques_created_at_index ON public.physical_mail_cheques USING btree (created_at);


--
-- Name: physical_mail_contacts_address_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_contacts_address_status_index ON public.physical_mail_contacts USING btree (address_status);


--
-- Name: physical_mail_contacts_client_id_company_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_contacts_client_id_company_name_index ON public.physical_mail_contacts USING btree (client_id, company_name);


--
-- Name: physical_mail_contacts_postgrid_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_contacts_postgrid_id_index ON public.physical_mail_contacts USING btree (postgrid_id);


--
-- Name: physical_mail_letters_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_letters_created_at_index ON public.physical_mail_letters USING btree (created_at);


--
-- Name: physical_mail_orders_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_client_id_status_index ON public.physical_mail_orders USING btree (client_id, status);


--
-- Name: physical_mail_orders_company_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_company_id_created_at_index ON public.physical_mail_orders USING btree (company_id, created_at);


--
-- Name: physical_mail_orders_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_company_id_status_index ON public.physical_mail_orders USING btree (company_id, status);


--
-- Name: physical_mail_orders_location_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_location_index ON public.physical_mail_orders USING btree (latitude, longitude);


--
-- Name: physical_mail_orders_mailable_type_mailable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_mailable_type_mailable_id_index ON public.physical_mail_orders USING btree (mailable_type, mailable_id);


--
-- Name: physical_mail_orders_postgrid_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_postgrid_id_index ON public.physical_mail_orders USING btree (postgrid_id);


--
-- Name: physical_mail_orders_send_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_send_date_index ON public.physical_mail_orders USING btree (send_date);


--
-- Name: physical_mail_orders_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_status_index ON public.physical_mail_orders USING btree (status);


--
-- Name: physical_mail_orders_tracking_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_orders_tracking_number_index ON public.physical_mail_orders USING btree (tracking_number);


--
-- Name: physical_mail_postcards_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_postcards_created_at_index ON public.physical_mail_postcards USING btree (created_at);


--
-- Name: physical_mail_return_envelopes_contact_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_return_envelopes_contact_id_index ON public.physical_mail_return_envelopes USING btree (contact_id);


--
-- Name: physical_mail_self_mailers_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_self_mailers_created_at_index ON public.physical_mail_self_mailers USING btree (created_at);


--
-- Name: physical_mail_templates_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_templates_name_index ON public.physical_mail_templates USING btree (name);


--
-- Name: physical_mail_templates_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_templates_type_is_active_index ON public.physical_mail_templates USING btree (type, is_active);


--
-- Name: physical_mail_webhooks_type_processed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX physical_mail_webhooks_type_processed_at_index ON public.physical_mail_webhooks USING btree (type, processed_at);


--
-- Name: portal_notifications_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_category_index ON public.portal_notifications USING btree (category);


--
-- Name: portal_notifications_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_client_id_index ON public.portal_notifications USING btree (client_id);


--
-- Name: portal_notifications_company_client_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_company_client_idx ON public.portal_notifications USING btree (company_id, client_id);


--
-- Name: portal_notifications_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_company_id_index ON public.portal_notifications USING btree (company_id);


--
-- Name: portal_notifications_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_contract_id_index ON public.portal_notifications USING btree (contract_id);


--
-- Name: portal_notifications_display_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_display_idx ON public.portal_notifications USING btree (show_in_portal, is_dismissed, expires_at);


--
-- Name: portal_notifications_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_expires_at_index ON public.portal_notifications USING btree (expires_at);


--
-- Name: portal_notifications_group_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_group_key_index ON public.portal_notifications USING btree (group_key);


--
-- Name: portal_notifications_invoice_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_invoice_id_index ON public.portal_notifications USING btree (invoice_id);


--
-- Name: portal_notifications_is_dismissed_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_is_dismissed_index ON public.portal_notifications USING btree (is_dismissed);


--
-- Name: portal_notifications_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_is_read_index ON public.portal_notifications USING btree (is_read);


--
-- Name: portal_notifications_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_parent_id_index ON public.portal_notifications USING btree (parent_id);


--
-- Name: portal_notifications_payment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_payment_id_index ON public.portal_notifications USING btree (payment_id);


--
-- Name: portal_notifications_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_priority_index ON public.portal_notifications USING btree (priority);


--
-- Name: portal_notifications_schedule_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_schedule_idx ON public.portal_notifications USING btree (scheduled_at, status);


--
-- Name: portal_notifications_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_scheduled_at_index ON public.portal_notifications USING btree (scheduled_at);


--
-- Name: portal_notifications_show_in_portal_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_show_in_portal_index ON public.portal_notifications USING btree (show_in_portal);


--
-- Name: portal_notifications_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_status_index ON public.portal_notifications USING btree (status);


--
-- Name: portal_notifications_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_ticket_id_index ON public.portal_notifications USING btree (ticket_id);


--
-- Name: portal_notifications_type_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_type_idx ON public.portal_notifications USING btree (type, category);


--
-- Name: portal_notifications_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portal_notifications_type_index ON public.portal_notifications USING btree (type);


--
-- Name: pricing_rules_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pricing_rules_company_id_client_id_index ON public.pricing_rules USING btree (company_id, client_id);


--
-- Name: pricing_rules_company_id_product_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pricing_rules_company_id_product_id_is_active_index ON public.pricing_rules USING btree (company_id, product_id, is_active);


--
-- Name: pricing_rules_promo_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pricing_rules_promo_code_index ON public.pricing_rules USING btree (promo_code);


--
-- Name: pricing_rules_valid_from_valid_until_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pricing_rules_valid_from_valid_until_index ON public.pricing_rules USING btree (valid_from, valid_until);


--
-- Name: products_base_price_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_base_price_index ON public.products USING btree (base_price);


--
-- Name: products_billing_model_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_billing_model_index ON public.products USING btree (billing_model);


--
-- Name: products_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_category_id_index ON public.products USING btree (category_id);


--
-- Name: products_company_id_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_company_id_category_id_index ON public.products USING btree (company_id, category_id);


--
-- Name: products_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_company_id_index ON public.products USING btree (company_id);


--
-- Name: products_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_company_id_is_active_index ON public.products USING btree (company_id, is_active);


--
-- Name: products_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_company_id_type_index ON public.products USING btree (company_id, type);


--
-- Name: products_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_name_index ON public.products USING btree (name);


--
-- Name: products_sku_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_sku_index ON public.products USING btree (sku);


--
-- Name: products_tax_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_tax_profile_id_index ON public.products USING btree (tax_profile_id);


--
-- Name: products_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_type_index ON public.products USING btree (type);


--
-- Name: project_expenses_project_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_expenses_project_id_date_index ON public.project_expenses USING btree (project_id, date);


--
-- Name: project_expenses_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_expenses_status_index ON public.project_expenses USING btree (status);


--
-- Name: project_members_company_id_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_members_company_id_project_id_index ON public.project_members USING btree (company_id, project_id);


--
-- Name: project_members_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_members_company_id_user_id_index ON public.project_members USING btree (company_id, user_id);


--
-- Name: project_milestones_company_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_milestones_company_id_due_date_index ON public.project_milestones USING btree (company_id, due_date);


--
-- Name: project_milestones_company_id_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_milestones_company_id_project_id_index ON public.project_milestones USING btree (company_id, project_id);


--
-- Name: project_milestones_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_milestones_company_id_status_index ON public.project_milestones USING btree (company_id, status);


--
-- Name: project_tasks_company_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_tasks_company_id_due_date_index ON public.project_tasks USING btree (company_id, due_date);


--
-- Name: project_tasks_company_id_milestone_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_tasks_company_id_milestone_id_index ON public.project_tasks USING btree (company_id, milestone_id);


--
-- Name: project_tasks_company_id_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_tasks_company_id_project_id_index ON public.project_tasks USING btree (company_id, project_id);


--
-- Name: project_tasks_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_tasks_company_id_status_index ON public.project_tasks USING btree (company_id, status);


--
-- Name: project_templates_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_templates_company_id_category_index ON public.project_templates USING btree (company_id, category);


--
-- Name: project_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_templates_company_id_is_active_index ON public.project_templates USING btree (company_id, is_active);


--
-- Name: project_time_entries_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_time_entries_billable_index ON public.project_time_entries USING btree (billable);


--
-- Name: project_time_entries_project_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_time_entries_project_id_date_index ON public.project_time_entries USING btree (project_id, date);


--
-- Name: project_time_entries_user_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_time_entries_user_id_date_index ON public.project_time_entries USING btree (user_id, date);


--
-- Name: projects_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_archived_at_index ON public.projects USING btree (archived_at);


--
-- Name: projects_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_client_id_index ON public.projects USING btree (client_id);


--
-- Name: projects_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_company_id_client_id_index ON public.projects USING btree (company_id, client_id);


--
-- Name: projects_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_company_id_index ON public.projects USING btree (company_id);


--
-- Name: projects_completed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_completed_at_index ON public.projects USING btree (completed_at);


--
-- Name: projects_due_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_due_index ON public.projects USING btree (due);


--
-- Name: projects_manager_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_manager_id_index ON public.projects USING btree (manager_id);


--
-- Name: projects_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_name_index ON public.projects USING btree (name);


--
-- Name: push_subscriptions_subscribable_type_subscribable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX push_subscriptions_subscribable_type_subscribable_id_index ON public.push_subscriptions USING btree (subscribable_type, subscribable_id);


--
-- Name: quick_action_favorites_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quick_action_favorites_user_id_index ON public.quick_action_favorites USING btree (user_id);


--
-- Name: quote_approvals_approval_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_approvals_approval_level_index ON public.quote_approvals USING btree (approval_level);


--
-- Name: quote_approvals_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_approvals_archived_at_index ON public.quote_approvals USING btree (archived_at);


--
-- Name: quote_approvals_company_id_quote_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_approvals_company_id_quote_id_index ON public.quote_approvals USING btree (company_id, quote_id);


--
-- Name: quote_approvals_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_approvals_user_id_status_index ON public.quote_approvals USING btree (user_id, status);


--
-- Name: quote_templates_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_templates_category_index ON public.quote_templates USING btree (category);


--
-- Name: quote_templates_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_templates_company_id_category_index ON public.quote_templates USING btree (company_id, category);


--
-- Name: quote_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_templates_company_id_is_active_index ON public.quote_templates USING btree (company_id, is_active);


--
-- Name: quote_templates_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_templates_created_by_index ON public.quote_templates USING btree (created_by);


--
-- Name: quote_templates_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_templates_name_index ON public.quote_templates USING btree (name);


--
-- Name: quote_versions_company_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_versions_company_id_created_at_index ON public.quote_versions USING btree (company_id, created_at);


--
-- Name: quote_versions_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_versions_created_by_index ON public.quote_versions USING btree (created_by);


--
-- Name: quote_versions_quote_id_version_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quote_versions_quote_id_version_number_index ON public.quote_versions USING btree (quote_id, version_number);


--
-- Name: quotes_approval_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_approval_status_index ON public.quotes USING btree (approval_status);


--
-- Name: quotes_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_archived_at_index ON public.quotes USING btree (archived_at);


--
-- Name: quotes_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_client_id_index ON public.quotes USING btree (client_id);


--
-- Name: quotes_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_client_id_status_index ON public.quotes USING btree (client_id, status);


--
-- Name: quotes_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_company_id_client_id_index ON public.quotes USING btree (company_id, client_id);


--
-- Name: quotes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_company_id_index ON public.quotes USING btree (company_id);


--
-- Name: quotes_company_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_company_status_idx ON public.quotes USING btree (company_id, status);


--
-- Name: quotes_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_date_index ON public.quotes USING btree (date);


--
-- Name: quotes_expire_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_expire_index ON public.quotes USING btree (expire);


--
-- Name: quotes_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_number_index ON public.quotes USING btree (number);


--
-- Name: quotes_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_status_index ON public.quotes USING btree (status);


--
-- Name: quotes_url_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX quotes_url_key_index ON public.quotes USING btree (url_key);


--
-- Name: rate_cards_client_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rate_cards_client_id_is_active_index ON public.rate_cards USING btree (client_id, is_active);


--
-- Name: rate_cards_client_id_service_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rate_cards_client_id_service_type_index ON public.rate_cards USING btree (client_id, service_type);


--
-- Name: rate_cards_effective_from_effective_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rate_cards_effective_from_effective_to_index ON public.rate_cards USING btree (effective_from, effective_to);


--
-- Name: recurring_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_archived_at_index ON public.recurring USING btree (archived_at);


--
-- Name: recurring_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_client_id_index ON public.recurring USING btree (client_id);


--
-- Name: recurring_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_client_id_status_index ON public.recurring USING btree (client_id, status);


--
-- Name: recurring_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_company_id_client_id_index ON public.recurring USING btree (company_id, client_id);


--
-- Name: recurring_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_company_id_index ON public.recurring USING btree (company_id);


--
-- Name: recurring_frequency_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_frequency_index ON public.recurring USING btree (frequency);


--
-- Name: recurring_next_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_next_date_index ON public.recurring USING btree (next_date);


--
-- Name: recurring_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_number_index ON public.recurring USING btree (number);


--
-- Name: recurring_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_status_index ON public.recurring USING btree (status);


--
-- Name: recurring_status_next_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_status_next_date_index ON public.recurring USING btree (status, next_date);


--
-- Name: recurring_tickets_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_tickets_company_id_client_id_index ON public.recurring_tickets USING btree (company_id, client_id);


--
-- Name: recurring_tickets_company_id_next_run_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_tickets_company_id_next_run_index ON public.recurring_tickets USING btree (company_id, next_run);


--
-- Name: recurring_tickets_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recurring_tickets_company_id_status_index ON public.recurring_tickets USING btree (company_id, status);


--
-- Name: report_exports_company_id_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_exports_company_id_expires_at_index ON public.report_exports USING btree (company_id, expires_at);


--
-- Name: report_exports_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_exports_company_id_status_index ON public.report_exports USING btree (company_id, status);


--
-- Name: report_exports_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_exports_company_id_user_id_index ON public.report_exports USING btree (company_id, user_id);


--
-- Name: report_metrics_company_id_metric_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_metrics_company_id_metric_date_index ON public.report_metrics USING btree (company_id, metric_date);


--
-- Name: report_metrics_company_id_metric_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_metrics_company_id_metric_name_index ON public.report_metrics USING btree (company_id, metric_name);


--
-- Name: report_metrics_company_id_metric_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_metrics_company_id_metric_type_index ON public.report_metrics USING btree (company_id, metric_type);


--
-- Name: report_subscriptions_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_subscriptions_company_id_is_active_index ON public.report_subscriptions USING btree (company_id, is_active);


--
-- Name: report_subscriptions_company_id_next_run_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_subscriptions_company_id_next_run_index ON public.report_subscriptions USING btree (company_id, next_run);


--
-- Name: report_subscriptions_company_id_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_subscriptions_company_id_template_id_index ON public.report_subscriptions USING btree (company_id, template_id);


--
-- Name: report_templates_company_id_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_templates_company_id_category_id_index ON public.report_templates USING btree (company_id, category_id);


--
-- Name: report_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_templates_company_id_is_active_index ON public.report_templates USING btree (company_id, is_active);


--
-- Name: report_templates_company_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_templates_company_id_type_index ON public.report_templates USING btree (company_id, type);


--
-- Name: rmm_client_mappings_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rmm_client_mappings_company_id_is_active_index ON public.rmm_client_mappings USING btree (company_id, is_active);


--
-- Name: saved_reports_company_id_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX saved_reports_company_id_template_id_index ON public.saved_reports USING btree (company_id, template_id);


--
-- Name: saved_reports_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX saved_reports_company_id_user_id_index ON public.saved_reports USING btree (company_id, user_id);


--
-- Name: scheduler_cleanup_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scheduler_cleanup_idx ON public.scheduler_coordination USING btree (created_at);


--
-- Name: scheduler_heartbeat_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scheduler_heartbeat_idx ON public.scheduler_coordination USING btree (job_name, heartbeat_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_company_id_index ON public.settings USING btree (company_id);


--
-- Name: settings_configurations_company_id_domain_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_configurations_company_id_domain_category_index ON public.settings_configurations USING btree (company_id, domain, category);


--
-- Name: settings_configurations_company_id_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_configurations_company_id_domain_index ON public.settings_configurations USING btree (company_id, domain);


--
-- Name: shifts_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX shifts_company_id_is_active_index ON public.shifts USING btree (company_id, is_active);


--
-- Name: slas_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX slas_company_id_is_active_index ON public.slas USING btree (company_id, is_active);


--
-- Name: slas_company_id_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX slas_company_id_is_default_index ON public.slas USING btree (company_id, is_default);


--
-- Name: slas_effective_from_effective_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX slas_effective_from_effective_to_index ON public.slas USING btree (effective_from, effective_to);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: subscription_plans_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plans_is_active_index ON public.subscription_plans USING btree (is_active);


--
-- Name: subscription_plans_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plans_is_active_sort_order_index ON public.subscription_plans USING btree (is_active, sort_order);


--
-- Name: subscription_plans_pricing_model_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plans_pricing_model_is_active_index ON public.subscription_plans USING btree (pricing_model, is_active);


--
-- Name: subsidiary_permissions_grantee_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsidiary_permissions_grantee_company_id_index ON public.subsidiary_permissions USING btree (grantee_company_id);


--
-- Name: subsidiary_permissions_granter_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsidiary_permissions_granter_company_id_index ON public.subsidiary_permissions USING btree (granter_company_id);


--
-- Name: subsidiary_permissions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsidiary_permissions_user_id_index ON public.subsidiary_permissions USING btree (user_id);


--
-- Name: suspicious_login_attempts_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_company_id_status_index ON public.suspicious_login_attempts USING btree (company_id, status);


--
-- Name: suspicious_login_attempts_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_expires_at_index ON public.suspicious_login_attempts USING btree (expires_at);


--
-- Name: suspicious_login_attempts_expires_at_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_expires_at_status_index ON public.suspicious_login_attempts USING btree (expires_at, status);


--
-- Name: suspicious_login_attempts_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_ip_address_index ON public.suspicious_login_attempts USING btree (ip_address);


--
-- Name: suspicious_login_attempts_risk_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_risk_score_index ON public.suspicious_login_attempts USING btree (risk_score);


--
-- Name: suspicious_login_attempts_risk_score_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_risk_score_status_index ON public.suspicious_login_attempts USING btree (risk_score, status);


--
-- Name: suspicious_login_attempts_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_status_index ON public.suspicious_login_attempts USING btree (status);


--
-- Name: suspicious_login_attempts_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX suspicious_login_attempts_user_id_status_index ON public.suspicious_login_attempts USING btree (user_id, status);


--
-- Name: tags_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tags_archived_at_index ON public.tags USING btree (archived_at);


--
-- Name: tags_company_id_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tags_company_id_name_index ON public.tags USING btree (company_id, name);


--
-- Name: tags_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tags_type_index ON public.tags USING btree (type);


--
-- Name: task_checklist_items_company_id_is_completed_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_checklist_items_company_id_is_completed_index ON public.task_checklist_items USING btree (company_id, is_completed);


--
-- Name: task_checklist_items_company_id_task_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_checklist_items_company_id_task_id_index ON public.task_checklist_items USING btree (company_id, task_id);


--
-- Name: task_dependencies_company_id_depends_on_task_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_dependencies_company_id_depends_on_task_id_index ON public.task_dependencies USING btree (company_id, depends_on_task_id);


--
-- Name: task_dependencies_company_id_task_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_dependencies_company_id_task_id_index ON public.task_dependencies USING btree (company_id, task_id);


--
-- Name: task_watchers_company_id_task_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_watchers_company_id_task_id_index ON public.task_watchers USING btree (company_id, task_id);


--
-- Name: task_watchers_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX task_watchers_company_id_user_id_index ON public.task_watchers USING btree (company_id, user_id);


--
-- Name: tax_api_query_cache_query_hash_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_api_query_cache_query_hash_expires_at_index ON public.tax_api_query_cache USING btree (query_hash, expires_at);


--
-- Name: tax_calculations_calculable_type_calculable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_calculations_calculable_type_calculable_id_index ON public.tax_calculations USING btree (calculable_type, calculable_id);


--
-- Name: taxes_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taxes_archived_at_index ON public.taxes USING btree (archived_at);


--
-- Name: taxes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taxes_company_id_index ON public.taxes USING btree (company_id);


--
-- Name: taxes_company_id_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taxes_company_id_name_index ON public.taxes USING btree (company_id, name);


--
-- Name: taxes_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taxes_name_index ON public.taxes USING btree (name);


--
-- Name: taxes_percent_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taxes_percent_index ON public.taxes USING btree (percent);


--
-- Name: ticket_assignments_company_id_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_assignments_company_id_assigned_to_index ON public.ticket_assignments USING btree (company_id, assigned_to);


--
-- Name: ticket_assignments_ticket_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_assignments_ticket_id_is_active_index ON public.ticket_assignments USING btree (ticket_id, is_active);


--
-- Name: ticket_calendar_events_company_id_start_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_calendar_events_company_id_start_time_index ON public.ticket_calendar_events USING btree (company_id, start_time);


--
-- Name: ticket_calendar_events_company_id_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_calendar_events_company_id_ticket_id_index ON public.ticket_calendar_events USING btree (company_id, ticket_id);


--
-- Name: ticket_comment_attachments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comment_attachments_company_id_index ON public.ticket_comment_attachments USING btree (company_id);


--
-- Name: ticket_comment_attachments_ticket_comment_id_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comment_attachments_ticket_comment_id_company_id_index ON public.ticket_comment_attachments USING btree (ticket_comment_id, company_id);


--
-- Name: ticket_comment_attachments_ticket_comment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comment_attachments_ticket_comment_id_index ON public.ticket_comment_attachments USING btree (ticket_comment_id);


--
-- Name: ticket_comments_author_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_author_id_index ON public.ticket_comments USING btree (author_id);


--
-- Name: ticket_comments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_company_id_index ON public.ticket_comments USING btree (company_id);


--
-- Name: ticket_comments_company_id_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_company_id_ticket_id_index ON public.ticket_comments USING btree (company_id, ticket_id);


--
-- Name: ticket_comments_is_resolution_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_is_resolution_index ON public.ticket_comments USING btree (is_resolution);


--
-- Name: ticket_comments_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_parent_id_index ON public.ticket_comments USING btree (parent_id);


--
-- Name: ticket_comments_sentiment_label_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_sentiment_label_created_at_index ON public.ticket_comments USING btree (sentiment_label, created_at);


--
-- Name: ticket_comments_source_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_source_index ON public.ticket_comments USING btree (source);


--
-- Name: ticket_comments_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_ticket_id_index ON public.ticket_comments USING btree (ticket_id);


--
-- Name: ticket_comments_ticket_id_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_ticket_id_visibility_index ON public.ticket_comments USING btree (ticket_id, visibility);


--
-- Name: ticket_comments_time_entry_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_time_entry_id_index ON public.ticket_comments USING btree (time_entry_id);


--
-- Name: ticket_comments_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_visibility_index ON public.ticket_comments USING btree (visibility);


--
-- Name: ticket_priority_queue_company_id_priority_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priority_queue_company_id_priority_score_index ON public.ticket_priority_queue USING btree (company_id, priority_score);


--
-- Name: ticket_priority_queue_company_id_queue_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priority_queue_company_id_queue_time_index ON public.ticket_priority_queue USING btree (company_id, queue_time);


--
-- Name: ticket_priority_queues_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priority_queues_company_id_is_active_index ON public.ticket_priority_queues USING btree (company_id, is_active);


--
-- Name: ticket_priority_queues_company_id_queue_position_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priority_queues_company_id_queue_position_index ON public.ticket_priority_queues USING btree (company_id, queue_position);


--
-- Name: ticket_priority_queues_sla_deadline_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priority_queues_sla_deadline_index ON public.ticket_priority_queues USING btree (sla_deadline);


--
-- Name: ticket_ratings_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_ratings_client_id_created_at_index ON public.ticket_ratings USING btree (client_id, created_at);


--
-- Name: ticket_ratings_rating_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_ratings_rating_index ON public.ticket_ratings USING btree (rating);


--
-- Name: ticket_ratings_ticket_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_ratings_ticket_id_created_at_index ON public.ticket_ratings USING btree (ticket_id, created_at);


--
-- Name: ticket_replies_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_archived_at_index ON public.ticket_replies USING btree (archived_at);


--
-- Name: ticket_replies_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_company_id_index ON public.ticket_replies USING btree (company_id);


--
-- Name: ticket_replies_company_id_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_company_id_ticket_id_index ON public.ticket_replies USING btree (company_id, ticket_id);


--
-- Name: ticket_replies_replied_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_replied_by_index ON public.ticket_replies USING btree (replied_by);


--
-- Name: ticket_replies_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_ticket_id_index ON public.ticket_replies USING btree (ticket_id);


--
-- Name: ticket_replies_ticket_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_ticket_id_type_index ON public.ticket_replies USING btree (ticket_id, type);


--
-- Name: ticket_replies_time_worked_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_time_worked_index ON public.ticket_replies USING btree (time_worked);


--
-- Name: ticket_replies_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_replies_type_index ON public.ticket_replies USING btree (type);


--
-- Name: ticket_status_transitions_company_id_from_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_status_transitions_company_id_from_status_index ON public.ticket_status_transitions USING btree (company_id, from_status);


--
-- Name: ticket_templates_company_id_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_templates_company_id_category_index ON public.ticket_templates USING btree (company_id, category);


--
-- Name: ticket_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_templates_company_id_is_active_index ON public.ticket_templates USING btree (company_id, is_active);


--
-- Name: ticket_time_entries_company_id_is_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_time_entries_company_id_is_billable_index ON public.ticket_time_entries USING btree (company_id, is_billable);


--
-- Name: ticket_time_entries_company_id_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_time_entries_company_id_ticket_id_index ON public.ticket_time_entries USING btree (company_id, ticket_id);


--
-- Name: ticket_time_entries_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_time_entries_company_id_user_id_index ON public.ticket_time_entries USING btree (company_id, user_id);


--
-- Name: ticket_time_entries_user_id_entry_type_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_time_entries_user_id_entry_type_started_at_index ON public.ticket_time_entries USING btree (user_id, entry_type, started_at);


--
-- Name: ticket_workflows_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_workflows_company_id_is_active_index ON public.ticket_workflows USING btree (company_id, is_active);


--
-- Name: ticket_workflows_company_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_workflows_company_id_sort_order_index ON public.ticket_workflows USING btree (company_id, sort_order);


--
-- Name: tickets_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_archived_at_index ON public.tickets USING btree (archived_at);


--
-- Name: tickets_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_assigned_to_index ON public.tickets USING btree (assigned_to);


--
-- Name: tickets_assigned_to_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_assigned_to_status_index ON public.tickets USING btree (assigned_to, status);


--
-- Name: tickets_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_billable_index ON public.tickets USING btree (billable);


--
-- Name: tickets_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_client_id_index ON public.tickets USING btree (client_id);


--
-- Name: tickets_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_client_id_status_index ON public.tickets USING btree (client_id, status);


--
-- Name: tickets_closed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_closed_at_index ON public.tickets USING btree (closed_at);


--
-- Name: tickets_company_id_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_company_id_client_id_index ON public.tickets USING btree (company_id, client_id);


--
-- Name: tickets_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_company_id_index ON public.tickets USING btree (company_id);


--
-- Name: tickets_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_created_by_index ON public.tickets USING btree (created_by);


--
-- Name: tickets_is_resolved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_is_resolved_index ON public.tickets USING btree (is_resolved);


--
-- Name: tickets_is_resolved_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_is_resolved_status_index ON public.tickets USING btree (is_resolved, status);


--
-- Name: tickets_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_number_index ON public.tickets USING btree (number);


--
-- Name: tickets_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_priority_index ON public.tickets USING btree (priority);


--
-- Name: tickets_resolved_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_resolved_at_index ON public.tickets USING btree (resolved_at);


--
-- Name: tickets_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_scheduled_at_index ON public.tickets USING btree (scheduled_at);


--
-- Name: tickets_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_status_index ON public.tickets USING btree (status);


--
-- Name: time_entries_company_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_company_id_billable_index ON public.time_entries USING btree (company_id, billable);


--
-- Name: time_entries_user_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_user_id_date_index ON public.time_entries USING btree (user_id, date);


--
-- Name: time_entry_templates_company_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entry_templates_company_id_is_active_index ON public.time_entry_templates USING btree (company_id, is_active);


--
-- Name: time_entry_templates_work_type_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entry_templates_work_type_category_index ON public.time_entry_templates USING btree (work_type, category);


--
-- Name: time_off_requests_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_off_requests_company_id_status_index ON public.time_off_requests USING btree (company_id, status);


--
-- Name: time_off_requests_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_off_requests_company_id_user_id_index ON public.time_off_requests USING btree (company_id, user_id);


--
-- Name: time_off_requests_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_off_requests_start_date_end_date_index ON public.time_off_requests USING btree (start_date, end_date);


--
-- Name: trusted_devices_company_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_company_id_user_id_index ON public.trusted_devices USING btree (company_id, user_id);


--
-- Name: trusted_devices_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_expires_at_index ON public.trusted_devices USING btree (expires_at);


--
-- Name: trusted_devices_expires_at_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_expires_at_is_active_index ON public.trusted_devices USING btree (expires_at, is_active);


--
-- Name: trusted_devices_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_is_active_index ON public.trusted_devices USING btree (is_active);


--
-- Name: trusted_devices_last_used_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_last_used_at_index ON public.trusted_devices USING btree (last_used_at);


--
-- Name: trusted_devices_trust_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_trust_level_index ON public.trusted_devices USING btree (trust_level);


--
-- Name: trusted_devices_trust_level_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_trust_level_is_active_index ON public.trusted_devices USING btree (trust_level, is_active);


--
-- Name: trusted_devices_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trusted_devices_user_id_is_active_index ON public.trusted_devices USING btree (user_id, is_active);


--
-- Name: user_clients_access_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_clients_access_level_index ON public.user_clients USING btree (access_level);


--
-- Name: user_clients_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_clients_client_id_index ON public.user_clients USING btree (client_id);


--
-- Name: user_clients_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_clients_expires_at_index ON public.user_clients USING btree (expires_at);


--
-- Name: user_clients_is_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_clients_is_primary_index ON public.user_clients USING btree (is_primary);


--
-- Name: user_dashboard_configs_company_id_is_shared_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_dashboard_configs_company_id_is_shared_index ON public.user_dashboard_configs USING btree (company_id, is_shared);


--
-- Name: user_favorite_clients_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_favorite_clients_user_id_created_at_index ON public.user_favorite_clients USING btree (user_id, created_at);


--
-- Name: user_roles_role_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_roles_role_id_index ON public.user_roles USING btree (role_id);


--
-- Name: user_roles_user_id_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_roles_user_id_company_id_index ON public.user_roles USING btree (user_id, company_id);


--
-- Name: user_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_settings_company_id_index ON public.user_settings USING btree (company_id);


--
-- Name: user_settings_company_id_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_settings_company_id_role_index ON public.user_settings USING btree (company_id, role);


--
-- Name: user_settings_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_settings_role_index ON public.user_settings USING btree (role);


--
-- Name: user_settings_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_settings_user_id_index ON public.user_settings USING btree (user_id);


--
-- Name: user_settings_user_id_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_settings_user_id_role_index ON public.user_settings USING btree (user_id, role);


--
-- Name: user_widget_instances_instance_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_widget_instances_instance_id_index ON public.user_widget_instances USING btree (instance_id);


--
-- Name: users_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_company_id_index ON public.users USING btree (company_id);


--
-- Name: users_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_company_id_status_index ON public.users USING btree (company_id, status);


--
-- Name: users_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_email_index ON public.users USING btree (email);


--
-- Name: users_email_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_email_status_index ON public.users USING btree (email, status);


--
-- Name: users_employment_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_employment_type_index ON public.users USING btree (employment_type);


--
-- Name: users_is_overtime_exempt_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_is_overtime_exempt_index ON public.users USING btree (is_overtime_exempt);


--
-- Name: users_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_status_index ON public.users USING btree (status);


--
-- Name: vendors_archived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_archived_at_index ON public.vendors USING btree (archived_at);


--
-- Name: vendors_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_client_id_index ON public.vendors USING btree (client_id);


--
-- Name: vendors_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_company_id_index ON public.vendors USING btree (company_id);


--
-- Name: vendors_company_id_template_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_company_id_template_index ON public.vendors USING btree (company_id, template);


--
-- Name: vendors_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_email_index ON public.vendors USING btree (email);


--
-- Name: vendors_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_name_index ON public.vendors USING btree (name);


--
-- Name: vendors_template_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_template_index ON public.vendors USING btree (template);


--
-- Name: widget_data_cache_company_id_widget_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_data_cache_company_id_widget_type_index ON public.widget_data_cache USING btree (company_id, widget_type);


--
-- Name: widget_data_cache_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_data_cache_expires_at_index ON public.widget_data_cache USING btree (expires_at);


--
-- Name: account_holds account_holds_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_holds
    ADD CONSTRAINT account_holds_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: account_holds account_holds_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_holds
    ADD CONSTRAINT account_holds_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: accounts accounts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: analytics_snapshots analytics_snapshots_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.analytics_snapshots
    ADD CONSTRAINT analytics_snapshots_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: asset_depreciations asset_depreciations_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_depreciations
    ADD CONSTRAINT asset_depreciations_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: asset_depreciations asset_depreciations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_depreciations
    ADD CONSTRAINT asset_depreciations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: asset_maintenance asset_maintenance_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_maintenance
    ADD CONSTRAINT asset_maintenance_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: asset_maintenance asset_maintenance_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_maintenance
    ADD CONSTRAINT asset_maintenance_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: asset_maintenance asset_maintenance_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_maintenance
    ADD CONSTRAINT asset_maintenance_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: asset_warranties asset_warranties_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_warranties
    ADD CONSTRAINT asset_warranties_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: asset_warranties asset_warranties_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_warranties
    ADD CONSTRAINT asset_warranties_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: assets assets_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: assets assets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: assets assets_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE SET NULL;


--
-- Name: assets assets_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE SET NULL;


--
-- Name: assets assets_network_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_network_id_foreign FOREIGN KEY (network_id) REFERENCES public.networks(id) ON DELETE SET NULL;


--
-- Name: assets assets_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: attribution_touchpoints attribution_touchpoints_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.marketing_campaigns(id) ON DELETE CASCADE;


--
-- Name: attribution_touchpoints attribution_touchpoints_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: attribution_touchpoints attribution_touchpoints_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: attribution_touchpoints attribution_touchpoints_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: attribution_touchpoints attribution_touchpoints_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribution_touchpoints
    ADD CONSTRAINT attribution_touchpoints_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: audit_logs audit_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE SET NULL;


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: auto_payments auto_payments_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auto_payments
    ADD CONSTRAINT auto_payments_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: auto_payments auto_payments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auto_payments
    ADD CONSTRAINT auto_payments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: auto_payments auto_payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auto_payments
    ADD CONSTRAINT auto_payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: bouncer_assigned_roles bouncer_assigned_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_assigned_roles
    ADD CONSTRAINT bouncer_assigned_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.bouncer_roles(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: bouncer_permissions bouncer_permissions_ability_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bouncer_permissions
    ADD CONSTRAINT bouncer_permissions_ability_id_foreign FOREIGN KEY (ability_id) REFERENCES public.bouncer_abilities(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: campaign_enrollments campaign_enrollments_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_enrollments
    ADD CONSTRAINT campaign_enrollments_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.marketing_campaigns(id) ON DELETE CASCADE;


--
-- Name: campaign_enrollments campaign_enrollments_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_enrollments
    ADD CONSTRAINT campaign_enrollments_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: campaign_enrollments campaign_enrollments_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_enrollments
    ADD CONSTRAINT campaign_enrollments_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: campaign_sequences campaign_sequences_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sequences
    ADD CONSTRAINT campaign_sequences_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.marketing_campaigns(id) ON DELETE CASCADE;


--
-- Name: cash_flow_projections cash_flow_projections_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_flow_projections
    ADD CONSTRAINT cash_flow_projections_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: categories categories_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: client_addresses client_addresses_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_addresses
    ADD CONSTRAINT client_addresses_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_addresses client_addresses_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_addresses
    ADD CONSTRAINT client_addresses_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_calendar_events client_calendar_events_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_calendar_events
    ADD CONSTRAINT client_calendar_events_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_calendar_events client_calendar_events_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_calendar_events
    ADD CONSTRAINT client_calendar_events_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_certificates client_certificates_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_certificates
    ADD CONSTRAINT client_certificates_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_certificates client_certificates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_certificates
    ADD CONSTRAINT client_certificates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_contacts client_contacts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_contacts
    ADD CONSTRAINT client_contacts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_contacts client_contacts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_contacts
    ADD CONSTRAINT client_contacts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_credentials client_credentials_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credentials
    ADD CONSTRAINT client_credentials_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_credentials client_credentials_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credentials
    ADD CONSTRAINT client_credentials_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_credit_applications client_credit_applications_applied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications
    ADD CONSTRAINT client_credit_applications_applied_by_foreign FOREIGN KEY (applied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_credit_applications client_credit_applications_client_credit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications
    ADD CONSTRAINT client_credit_applications_client_credit_id_foreign FOREIGN KEY (client_credit_id) REFERENCES public.client_credits(id) ON DELETE CASCADE;


--
-- Name: client_credit_applications client_credit_applications_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications
    ADD CONSTRAINT client_credit_applications_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_credit_applications client_credit_applications_unapplied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credit_applications
    ADD CONSTRAINT client_credit_applications_unapplied_by_foreign FOREIGN KEY (unapplied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_credits client_credits_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits
    ADD CONSTRAINT client_credits_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_credits client_credits_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits
    ADD CONSTRAINT client_credits_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_credits client_credits_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_credits
    ADD CONSTRAINT client_credits_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_documents client_documents_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_documents client_documents_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_domains client_domains_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_domains
    ADD CONSTRAINT client_domains_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_domains client_domains_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_domains
    ADD CONSTRAINT client_domains_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_files client_files_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_files
    ADD CONSTRAINT client_files_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_files client_files_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_files
    ADD CONSTRAINT client_files_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_it_documentation client_it_documentation_authored_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation
    ADD CONSTRAINT client_it_documentation_authored_by_foreign FOREIGN KEY (authored_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_it_documentation client_it_documentation_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation
    ADD CONSTRAINT client_it_documentation_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_it_documentation client_it_documentation_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation
    ADD CONSTRAINT client_it_documentation_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_it_documentation client_it_documentation_parent_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_it_documentation
    ADD CONSTRAINT client_it_documentation_parent_document_id_foreign FOREIGN KEY (parent_document_id) REFERENCES public.client_it_documentation(id) ON DELETE CASCADE;


--
-- Name: client_licenses client_licenses_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_licenses
    ADD CONSTRAINT client_licenses_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_licenses client_licenses_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_licenses
    ADD CONSTRAINT client_licenses_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_networks client_networks_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_networks
    ADD CONSTRAINT client_networks_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_networks client_networks_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_networks
    ADD CONSTRAINT client_networks_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_portal_sessions client_portal_sessions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions
    ADD CONSTRAINT client_portal_sessions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_portal_sessions client_portal_sessions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_sessions
    ADD CONSTRAINT client_portal_sessions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_portal_users client_portal_users_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_users
    ADD CONSTRAINT client_portal_users_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_portal_users client_portal_users_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_portal_users
    ADD CONSTRAINT client_portal_users_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_quotes client_quotes_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_quotes
    ADD CONSTRAINT client_quotes_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_quotes client_quotes_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_quotes
    ADD CONSTRAINT client_quotes_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_racks client_racks_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_racks
    ADD CONSTRAINT client_racks_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_racks client_racks_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_racks
    ADD CONSTRAINT client_racks_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_recurring_invoices client_recurring_invoices_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recurring_invoices
    ADD CONSTRAINT client_recurring_invoices_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_recurring_invoices client_recurring_invoices_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recurring_invoices
    ADD CONSTRAINT client_recurring_invoices_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_services client_services_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_services client_services_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_services client_services_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE SET NULL;


--
-- Name: client_services client_services_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: client_services client_services_recurring_billing_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_services
    ADD CONSTRAINT client_services_recurring_billing_id_foreign FOREIGN KEY (recurring_billing_id) REFERENCES public.recurring(id) ON DELETE SET NULL;


--
-- Name: client_tags client_tags_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags
    ADD CONSTRAINT client_tags_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_tags client_tags_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags
    ADD CONSTRAINT client_tags_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_tags client_tags_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_tags
    ADD CONSTRAINT client_tags_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: client_trips client_trips_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_trips
    ADD CONSTRAINT client_trips_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_trips client_trips_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_trips
    ADD CONSTRAINT client_trips_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: client_vendors client_vendors_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_vendors
    ADD CONSTRAINT client_vendors_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_vendors client_vendors_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_vendors
    ADD CONSTRAINT client_vendors_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: clients clients_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: collection_notes collection_notes_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_notes
    ADD CONSTRAINT collection_notes_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: communication_logs communication_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs
    ADD CONSTRAINT communication_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: communication_logs communication_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs
    ADD CONSTRAINT communication_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: communication_logs communication_logs_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs
    ADD CONSTRAINT communication_logs_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE SET NULL;


--
-- Name: communication_logs communication_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communication_logs
    ADD CONSTRAINT communication_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: company_customizations company_customizations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_customizations
    ADD CONSTRAINT company_customizations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: company_hierarchies company_hierarchies_ancestor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_hierarchies
    ADD CONSTRAINT company_hierarchies_ancestor_id_foreign FOREIGN KEY (ancestor_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: company_hierarchies company_hierarchies_descendant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_hierarchies
    ADD CONSTRAINT company_hierarchies_descendant_id_foreign FOREIGN KEY (descendant_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: company_mail_settings company_mail_settings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_mail_settings
    ADD CONSTRAINT company_mail_settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: company_subscriptions company_subscriptions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_subscriptions
    ADD CONSTRAINT company_subscriptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: company_subscriptions company_subscriptions_subscription_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_subscriptions
    ADD CONSTRAINT company_subscriptions_subscription_plan_id_foreign FOREIGN KEY (subscription_plan_id) REFERENCES public.subscription_plans(id) ON DELETE SET NULL;


--
-- Name: compliance_checks compliance_checks_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_checks
    ADD CONSTRAINT compliance_checks_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: compliance_requirements compliance_requirements_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compliance_requirements
    ADD CONSTRAINT compliance_requirements_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contacts contacts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: contacts contacts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contacts contacts_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE SET NULL;


--
-- Name: contacts contacts_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: contract_action_buttons contract_action_buttons_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_action_buttons
    ADD CONSTRAINT contract_action_buttons_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_amendments contract_amendments_applied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments
    ADD CONSTRAINT contract_amendments_applied_by_foreign FOREIGN KEY (applied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_amendments contract_amendments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments
    ADD CONSTRAINT contract_amendments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_amendments contract_amendments_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments
    ADD CONSTRAINT contract_amendments_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_amendments contract_amendments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_amendments
    ADD CONSTRAINT contract_amendments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contract_approvals contract_approvals_approver_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contract_approvals contract_approvals_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_approvals contract_approvals_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_approvals contract_approvals_delegated_to_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_delegated_to_id_foreign FOREIGN KEY (delegated_to_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_approvals contract_approvals_depends_on_approval_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_depends_on_approval_id_foreign FOREIGN KEY (depends_on_approval_id) REFERENCES public.contract_approvals(id) ON DELETE SET NULL;


--
-- Name: contract_approvals contract_approvals_escalated_to_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_approvals
    ADD CONSTRAINT contract_approvals_escalated_to_id_foreign FOREIGN KEY (escalated_to_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_asset_assignments contract_asset_assignments_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: contract_asset_assignments contract_asset_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_asset_assignments contract_asset_assignments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_asset_assignments contract_asset_assignments_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_asset_assignments contract_asset_assignments_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_asset_assignments
    ADD CONSTRAINT contract_asset_assignments_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_billing_calculations contract_billing_calculations_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_billing_calculations contract_billing_calculations_calculated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_calculated_by_foreign FOREIGN KEY (calculated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_billing_calculations contract_billing_calculations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_billing_calculations contract_billing_calculations_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_billing_calculations contract_billing_calculations_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: contract_billing_calculations contract_billing_calculations_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_calculations
    ADD CONSTRAINT contract_billing_calculations_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_billing_model_definitions contract_billing_model_definitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_billing_model_definitions
    ADD CONSTRAINT contract_billing_model_definitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_clauses contract_clauses_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses
    ADD CONSTRAINT contract_clauses_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_clauses contract_clauses_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses
    ADD CONSTRAINT contract_clauses_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_clauses contract_clauses_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_clauses
    ADD CONSTRAINT contract_clauses_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_comments contract_comments_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_comments contract_comments_negotiation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_negotiation_id_foreign FOREIGN KEY (negotiation_id) REFERENCES public.contract_negotiations(id) ON DELETE CASCADE;


--
-- Name: contract_comments contract_comments_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.contract_comments(id) ON DELETE CASCADE;


--
-- Name: contract_comments contract_comments_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_comments contract_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contract_comments contract_comments_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_comments
    ADD CONSTRAINT contract_comments_version_id_foreign FOREIGN KEY (version_id) REFERENCES public.contract_versions(id) ON DELETE CASCADE;


--
-- Name: contract_component_assignments contract_component_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments
    ADD CONSTRAINT contract_component_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_component_assignments contract_component_assignments_component_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments
    ADD CONSTRAINT contract_component_assignments_component_id_foreign FOREIGN KEY (component_id) REFERENCES public.contract_components(id) ON DELETE CASCADE;


--
-- Name: contract_component_assignments contract_component_assignments_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_component_assignments
    ADD CONSTRAINT contract_component_assignments_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_components contract_components_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_components
    ADD CONSTRAINT contract_components_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_components contract_components_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_components
    ADD CONSTRAINT contract_components_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_components contract_components_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_components
    ADD CONSTRAINT contract_components_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_configurations contract_configurations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations
    ADD CONSTRAINT contract_configurations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_configurations contract_configurations_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations
    ADD CONSTRAINT contract_configurations_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_configurations contract_configurations_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_configurations
    ADD CONSTRAINT contract_configurations_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_contact_assignments contract_contact_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_contact_assignments contract_contact_assignments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_contact_assignments contract_contact_assignments_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: contract_contact_assignments contract_contact_assignments_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_contact_assignments contract_contact_assignments_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_contact_assignments
    ADD CONSTRAINT contract_contact_assignments_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_dashboard_widgets contract_dashboard_widgets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_dashboard_widgets
    ADD CONSTRAINT contract_dashboard_widgets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_detail_configurations contract_detail_configurations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_detail_configurations
    ADD CONSTRAINT contract_detail_configurations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_field_definitions contract_field_definitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_field_definitions
    ADD CONSTRAINT contract_field_definitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_form_sections contract_form_sections_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_form_sections
    ADD CONSTRAINT contract_form_sections_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_invoice contract_invoice_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_invoice contract_invoice_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_invoice contract_invoice_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: contract_invoice contract_invoice_milestone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_invoice
    ADD CONSTRAINT contract_invoice_milestone_id_foreign FOREIGN KEY (milestone_id) REFERENCES public.contract_milestones(id) ON DELETE SET NULL;


--
-- Name: contract_list_configurations contract_list_configurations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_list_configurations
    ADD CONSTRAINT contract_list_configurations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_menu_sections contract_menu_sections_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_menu_sections
    ADD CONSTRAINT contract_menu_sections_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_milestones contract_milestones_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_milestones contract_milestones_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_milestones contract_milestones_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_milestones contract_milestones_depends_on_milestone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_depends_on_milestone_id_foreign FOREIGN KEY (depends_on_milestone_id) REFERENCES public.contract_milestones(id) ON DELETE SET NULL;


--
-- Name: contract_milestones contract_milestones_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_milestones
    ADD CONSTRAINT contract_milestones_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: contract_navigation_items contract_navigation_items_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_navigation_items
    ADD CONSTRAINT contract_navigation_items_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_negotiations contract_negotiations_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_negotiations contract_negotiations_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: contract_negotiations contract_negotiations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_negotiations contract_negotiations_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_negotiations contract_negotiations_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contract_negotiations contract_negotiations_current_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_current_version_id_foreign FOREIGN KEY (current_version_id) REFERENCES public.contract_versions(id) ON DELETE SET NULL;


--
-- Name: contract_negotiations contract_negotiations_quote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_negotiations
    ADD CONSTRAINT contract_negotiations_quote_id_foreign FOREIGN KEY (quote_id) REFERENCES public.quotes(id) ON DELETE SET NULL;


--
-- Name: contract_schedules contract_schedules_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_schedules contract_schedules_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_schedules contract_schedules_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_schedules contract_schedules_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_schedules contract_schedules_parent_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_parent_schedule_id_foreign FOREIGN KEY (parent_schedule_id) REFERENCES public.contract_schedules(id) ON DELETE SET NULL;


--
-- Name: contract_schedules contract_schedules_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_schedules
    ADD CONSTRAINT contract_schedules_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_signatures contract_signatures_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_signatures
    ADD CONSTRAINT contract_signatures_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_signatures contract_signatures_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_signatures
    ADD CONSTRAINT contract_signatures_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_status_definitions contract_status_definitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_definitions
    ADD CONSTRAINT contract_status_definitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_status_transitions contract_status_transitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_status_transitions
    ADD CONSTRAINT contract_status_transitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_template_clauses contract_template_clauses_clause_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_template_clauses
    ADD CONSTRAINT contract_template_clauses_clause_id_foreign FOREIGN KEY (clause_id) REFERENCES public.contract_clauses(id) ON DELETE CASCADE;


--
-- Name: contract_template_clauses contract_template_clauses_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_template_clauses
    ADD CONSTRAINT contract_template_clauses_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.contract_templates(id) ON DELETE CASCADE;


--
-- Name: contract_templates contract_templates_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_templates contract_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_templates contract_templates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_templates contract_templates_parent_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_parent_template_id_foreign FOREIGN KEY (parent_template_id) REFERENCES public.contract_templates(id) ON DELETE SET NULL;


--
-- Name: contract_templates contract_templates_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_templates
    ADD CONSTRAINT contract_templates_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_type_definitions contract_type_definitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_definitions
    ADD CONSTRAINT contract_type_definitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_type_form_mappings contract_type_form_mappings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_type_form_mappings
    ADD CONSTRAINT contract_type_form_mappings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contract_versions contract_versions_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions
    ADD CONSTRAINT contract_versions_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contract_versions contract_versions_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions
    ADD CONSTRAINT contract_versions_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES public.contracts(id) ON DELETE CASCADE;


--
-- Name: contract_versions contract_versions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_versions
    ADD CONSTRAINT contract_versions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: contract_view_definitions contract_view_definitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contract_view_definitions
    ADD CONSTRAINT contract_view_definitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contracts contracts_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contracts contracts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: contracts contracts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: contracts contracts_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contracts contracts_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: contracts contracts_quote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_quote_id_foreign FOREIGN KEY (quote_id) REFERENCES public.quotes(id) ON DELETE SET NULL;


--
-- Name: conversion_events conversion_events_attributed_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_attributed_campaign_id_foreign FOREIGN KEY (attributed_campaign_id) REFERENCES public.marketing_campaigns(id) ON DELETE SET NULL;


--
-- Name: conversion_events conversion_events_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: conversion_events conversion_events_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: conversion_events conversion_events_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: conversion_events conversion_events_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversion_events
    ADD CONSTRAINT conversion_events_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: credit_applications credit_applications_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_applications
    ADD CONSTRAINT credit_applications_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: credit_note_approvals credit_note_approvals_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_approvals
    ADD CONSTRAINT credit_note_approvals_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: credit_note_approvals credit_note_approvals_credit_note_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_approvals
    ADD CONSTRAINT credit_note_approvals_credit_note_id_foreign FOREIGN KEY (credit_note_id) REFERENCES public.credit_notes(id);


--
-- Name: credit_note_items credit_note_items_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_items
    ADD CONSTRAINT credit_note_items_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: credit_note_items credit_note_items_credit_note_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_note_items
    ADD CONSTRAINT credit_note_items_credit_note_id_foreign FOREIGN KEY (credit_note_id) REFERENCES public.credit_notes(id);


--
-- Name: credit_notes credit_notes_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes
    ADD CONSTRAINT credit_notes_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: credit_notes credit_notes_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes
    ADD CONSTRAINT credit_notes_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: credit_notes credit_notes_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_notes
    ADD CONSTRAINT credit_notes_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cross_company_users cross_company_users_authorized_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_authorized_by_foreign FOREIGN KEY (authorized_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cross_company_users cross_company_users_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: cross_company_users cross_company_users_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cross_company_users cross_company_users_delegated_from_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_delegated_from_foreign FOREIGN KEY (delegated_from) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cross_company_users cross_company_users_primary_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_primary_company_id_foreign FOREIGN KEY (primary_company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: cross_company_users cross_company_users_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cross_company_users cross_company_users_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_company_users
    ADD CONSTRAINT cross_company_users_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: custom_quick_actions custom_quick_actions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_quick_actions
    ADD CONSTRAINT custom_quick_actions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: custom_quick_actions custom_quick_actions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_quick_actions
    ADD CONSTRAINT custom_quick_actions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: dashboard_activity_logs dashboard_activity_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_activity_logs
    ADD CONSTRAINT dashboard_activity_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: dashboard_activity_logs dashboard_activity_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_activity_logs
    ADD CONSTRAINT dashboard_activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: dashboard_metrics dashboard_metrics_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_metrics
    ADD CONSTRAINT dashboard_metrics_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: dashboard_presets dashboard_presets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_presets
    ADD CONSTRAINT dashboard_presets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: dashboard_widgets dashboard_widgets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dashboard_widgets
    ADD CONSTRAINT dashboard_widgets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: device_mappings device_mappings_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings
    ADD CONSTRAINT device_mappings_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE SET NULL;


--
-- Name: device_mappings device_mappings_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings
    ADD CONSTRAINT device_mappings_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: device_mappings device_mappings_integration_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_mappings
    ADD CONSTRAINT device_mappings_integration_id_foreign FOREIGN KEY (integration_id) REFERENCES public.rmm_integrations(id) ON DELETE CASCADE;


--
-- Name: documents documents_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: documents documents_uploaded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dunning_actions dunning_actions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions
    ADD CONSTRAINT dunning_actions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dunning_actions dunning_actions_escalated_to_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions
    ADD CONSTRAINT dunning_actions_escalated_to_user_id_foreign FOREIGN KEY (escalated_to_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dunning_actions dunning_actions_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_actions
    ADD CONSTRAINT dunning_actions_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dunning_campaigns dunning_campaigns_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_campaigns
    ADD CONSTRAINT dunning_campaigns_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: dunning_campaigns dunning_campaigns_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_campaigns
    ADD CONSTRAINT dunning_campaigns_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dunning_sequences dunning_sequences_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_sequences
    ADD CONSTRAINT dunning_sequences_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.dunning_campaigns(id);


--
-- Name: dunning_sequences dunning_sequences_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dunning_sequences
    ADD CONSTRAINT dunning_sequences_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: email_accounts email_accounts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_accounts
    ADD CONSTRAINT email_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: email_accounts email_accounts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_accounts
    ADD CONSTRAINT email_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: email_attachments email_attachments_email_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments
    ADD CONSTRAINT email_attachments_email_message_id_foreign FOREIGN KEY (email_message_id) REFERENCES public.email_messages(id) ON DELETE CASCADE;


--
-- Name: email_folders email_folders_email_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_folders
    ADD CONSTRAINT email_folders_email_account_id_foreign FOREIGN KEY (email_account_id) REFERENCES public.email_accounts(id) ON DELETE CASCADE;


--
-- Name: email_messages email_messages_communication_log_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_communication_log_id_foreign FOREIGN KEY (communication_log_id) REFERENCES public.communication_logs(id);


--
-- Name: email_messages email_messages_email_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_email_account_id_foreign FOREIGN KEY (email_account_id) REFERENCES public.email_accounts(id) ON DELETE CASCADE;


--
-- Name: email_messages email_messages_email_folder_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_email_folder_id_foreign FOREIGN KEY (email_folder_id) REFERENCES public.email_folders(id) ON DELETE CASCADE;


--
-- Name: email_messages email_messages_reply_to_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_reply_to_message_id_foreign FOREIGN KEY (reply_to_message_id) REFERENCES public.email_messages(id);


--
-- Name: email_messages email_messages_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_messages
    ADD CONSTRAINT email_messages_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id);


--
-- Name: email_signatures email_signatures_email_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_signatures
    ADD CONSTRAINT email_signatures_email_account_id_foreign FOREIGN KEY (email_account_id) REFERENCES public.email_accounts(id) ON DELETE CASCADE;


--
-- Name: email_signatures email_signatures_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_signatures
    ADD CONSTRAINT email_signatures_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: email_templates email_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: email_templates email_templates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: email_tracking email_tracking_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.marketing_campaigns(id) ON DELETE CASCADE;


--
-- Name: email_tracking email_tracking_campaign_sequence_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_campaign_sequence_id_foreign FOREIGN KEY (campaign_sequence_id) REFERENCES public.campaign_sequences(id) ON DELETE CASCADE;


--
-- Name: email_tracking email_tracking_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: email_tracking email_tracking_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: email_tracking email_tracking_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_tracking
    ADD CONSTRAINT email_tracking_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: employee_schedules employee_schedules_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules
    ADD CONSTRAINT employee_schedules_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: employee_schedules employee_schedules_shift_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules
    ADD CONSTRAINT employee_schedules_shift_id_foreign FOREIGN KEY (shift_id) REFERENCES public.shifts(id) ON DELETE CASCADE;


--
-- Name: employee_schedules employee_schedules_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_schedules
    ADD CONSTRAINT employee_schedules_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: employee_time_entries employee_time_entries_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: employee_time_entries employee_time_entries_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: employee_time_entries employee_time_entries_pay_period_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_pay_period_id_foreign FOREIGN KEY (pay_period_id) REFERENCES public.pay_periods(id) ON DELETE SET NULL;


--
-- Name: employee_time_entries employee_time_entries_rejected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_rejected_by_foreign FOREIGN KEY (rejected_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: employee_time_entries employee_time_entries_shift_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_shift_id_foreign FOREIGN KEY (shift_id) REFERENCES public.shifts(id) ON DELETE SET NULL;


--
-- Name: employee_time_entries employee_time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_time_entries
    ADD CONSTRAINT employee_time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: expenses expenses_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: expenses expenses_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: expenses expenses_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: expenses expenses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: files files_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: files files_uploaded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: financial_reports financial_reports_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.financial_reports
    ADD CONSTRAINT financial_reports_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: hr_settings_overrides hr_settings_overrides_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_settings_overrides
    ADD CONSTRAINT hr_settings_overrides_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: in_app_notifications in_app_notifications_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.in_app_notifications
    ADD CONSTRAINT in_app_notifications_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: in_app_notifications in_app_notifications_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.in_app_notifications
    ADD CONSTRAINT in_app_notifications_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: in_app_notifications in_app_notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.in_app_notifications
    ADD CONSTRAINT in_app_notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: invoice_items invoice_items_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: invoice_items invoice_items_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: invoice_items invoice_items_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoice_items invoice_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: invoice_items invoice_items_quote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_quote_id_foreign FOREIGN KEY (quote_id) REFERENCES public.quotes(id) ON DELETE SET NULL;


--
-- Name: invoice_items invoice_items_recurring_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_recurring_id_foreign FOREIGN KEY (recurring_id) REFERENCES public.recurring(id) ON DELETE SET NULL;


--
-- Name: invoice_items invoice_items_tax_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_tax_id_foreign FOREIGN KEY (tax_id) REFERENCES public.taxes(id) ON DELETE SET NULL;


--
-- Name: invoices invoices_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: ip_lookup_logs ip_lookup_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_lookup_logs
    ADD CONSTRAINT ip_lookup_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: kpi_calculations kpi_calculations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_calculations
    ADD CONSTRAINT kpi_calculations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: kpi_targets kpi_targets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kpi_targets
    ADD CONSTRAINT kpi_targets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: lead_activities lead_activities_lead_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: lead_activities lead_activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lead_sources lead_sources_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_sources
    ADD CONSTRAINT lead_sources_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: leads leads_assigned_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_assigned_user_id_foreign FOREIGN KEY (assigned_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: leads leads_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: leads leads_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: leads leads_lead_source_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_lead_source_id_foreign FOREIGN KEY (lead_source_id) REFERENCES public.lead_sources(id) ON DELETE SET NULL;


--
-- Name: locations locations_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: locations locations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: locations locations_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE SET NULL;


--
-- Name: mail_queue mail_queue_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: mail_queue mail_queue_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: mail_queue mail_queue_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE SET NULL;


--
-- Name: mail_queue mail_queue_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_queue
    ADD CONSTRAINT mail_queue_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: mail_templates mail_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: marketing_campaigns marketing_campaigns_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaigns
    ADD CONSTRAINT marketing_campaigns_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: marketing_campaigns marketing_campaigns_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaigns
    ADD CONSTRAINT marketing_campaigns_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: networks networks_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.networks
    ADD CONSTRAINT networks_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: networks networks_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.networks
    ADD CONSTRAINT networks_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: networks networks_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.networks
    ADD CONSTRAINT networks_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE SET NULL;


--
-- Name: notification_preferences notification_preferences_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: notification_preferences notification_preferences_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: oauth_states oauth_states_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_states
    ADD CONSTRAINT oauth_states_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: oauth_states oauth_states_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_states
    ADD CONSTRAINT oauth_states_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: pay_periods pay_periods_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pay_periods
    ADD CONSTRAINT pay_periods_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pay_periods pay_periods_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pay_periods
    ADD CONSTRAINT pay_periods_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payment_applications payment_applications_applied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications
    ADD CONSTRAINT payment_applications_applied_by_foreign FOREIGN KEY (applied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_applications payment_applications_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications
    ADD CONSTRAINT payment_applications_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payment_applications payment_applications_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications
    ADD CONSTRAINT payment_applications_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: payment_applications payment_applications_unapplied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_applications
    ADD CONSTRAINT payment_applications_unapplied_by_foreign FOREIGN KEY (unapplied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_methods payment_methods_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payment_methods payment_methods_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payment_plans payment_plans_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_plans
    ADD CONSTRAINT payment_plans_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payment_plans payment_plans_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_plans
    ADD CONSTRAINT payment_plans_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payments payments_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payments payments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payments payments_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: permission_groups permission_groups_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_groups
    ADD CONSTRAINT permission_groups_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: physical_mail_bank_accounts physical_mail_bank_accounts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_bank_accounts
    ADD CONSTRAINT physical_mail_bank_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id);


--
-- Name: physical_mail_cheques physical_mail_cheques_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_cheques
    ADD CONSTRAINT physical_mail_cheques_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES public.physical_mail_bank_accounts(id);


--
-- Name: physical_mail_cheques physical_mail_cheques_from_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_cheques
    ADD CONSTRAINT physical_mail_cheques_from_contact_id_foreign FOREIGN KEY (from_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_cheques physical_mail_cheques_to_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_cheques
    ADD CONSTRAINT physical_mail_cheques_to_contact_id_foreign FOREIGN KEY (to_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_contacts physical_mail_contacts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_contacts
    ADD CONSTRAINT physical_mail_contacts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: physical_mail_letters physical_mail_letters_from_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_from_contact_id_foreign FOREIGN KEY (from_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_letters physical_mail_letters_return_envelope_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_return_envelope_id_foreign FOREIGN KEY (return_envelope_id) REFERENCES public.physical_mail_return_envelopes(id);


--
-- Name: physical_mail_letters physical_mail_letters_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.physical_mail_templates(id);


--
-- Name: physical_mail_letters physical_mail_letters_to_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_letters
    ADD CONSTRAINT physical_mail_letters_to_contact_id_foreign FOREIGN KEY (to_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_orders physical_mail_orders_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_orders
    ADD CONSTRAINT physical_mail_orders_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: physical_mail_orders physical_mail_orders_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_orders
    ADD CONSTRAINT physical_mail_orders_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: physical_mail_orders physical_mail_orders_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_orders
    ADD CONSTRAINT physical_mail_orders_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: physical_mail_postcards physical_mail_postcards_from_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_postcards
    ADD CONSTRAINT physical_mail_postcards_from_contact_id_foreign FOREIGN KEY (from_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_postcards physical_mail_postcards_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_postcards
    ADD CONSTRAINT physical_mail_postcards_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.physical_mail_templates(id);


--
-- Name: physical_mail_postcards physical_mail_postcards_to_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_postcards
    ADD CONSTRAINT physical_mail_postcards_to_contact_id_foreign FOREIGN KEY (to_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_return_envelopes physical_mail_return_envelopes_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_return_envelopes
    ADD CONSTRAINT physical_mail_return_envelopes_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_self_mailers physical_mail_self_mailers_from_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_self_mailers
    ADD CONSTRAINT physical_mail_self_mailers_from_contact_id_foreign FOREIGN KEY (from_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_self_mailers physical_mail_self_mailers_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_self_mailers
    ADD CONSTRAINT physical_mail_self_mailers_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.physical_mail_templates(id);


--
-- Name: physical_mail_self_mailers physical_mail_self_mailers_to_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_self_mailers
    ADD CONSTRAINT physical_mail_self_mailers_to_contact_id_foreign FOREIGN KEY (to_contact_id) REFERENCES public.physical_mail_contacts(id);


--
-- Name: physical_mail_settings physical_mail_settings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.physical_mail_settings
    ADD CONSTRAINT physical_mail_settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: portal_notifications portal_notifications_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal_notifications
    ADD CONSTRAINT portal_notifications_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: portal_notifications portal_notifications_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal_notifications
    ADD CONSTRAINT portal_notifications_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: portal_notifications portal_notifications_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portal_notifications
    ADD CONSTRAINT portal_notifications_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.portal_notifications(id) ON DELETE CASCADE;


--
-- Name: pricing_rules pricing_rules_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing_rules
    ADD CONSTRAINT pricing_rules_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: pricing_rules pricing_rules_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing_rules
    ADD CONSTRAINT pricing_rules_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: pricing_rules pricing_rules_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pricing_rules
    ADD CONSTRAINT pricing_rules_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_bundle_items product_bundle_items_bundle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundle_items
    ADD CONSTRAINT product_bundle_items_bundle_id_foreign FOREIGN KEY (bundle_id) REFERENCES public.product_bundles(id) ON DELETE CASCADE;


--
-- Name: product_bundle_items product_bundle_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundle_items
    ADD CONSTRAINT product_bundle_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_bundles product_bundles_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_bundles
    ADD CONSTRAINT product_bundles_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: product_tax_data product_tax_data_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tax_data
    ADD CONSTRAINT product_tax_data_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: product_tax_data product_tax_data_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tax_data
    ADD CONSTRAINT product_tax_data_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_tax_data product_tax_data_tax_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tax_data
    ADD CONSTRAINT product_tax_data_tax_profile_id_foreign FOREIGN KEY (tax_profile_id) REFERENCES public.tax_profiles(id) ON DELETE SET NULL;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: products products_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: products products_tax_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_tax_id_foreign FOREIGN KEY (tax_id) REFERENCES public.taxes(id) ON DELETE SET NULL;


--
-- Name: project_comments project_comments_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_comments
    ADD CONSTRAINT project_comments_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_comments project_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_comments
    ADD CONSTRAINT project_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: project_expenses project_expenses_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_expenses
    ADD CONSTRAINT project_expenses_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- Name: project_expenses project_expenses_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_expenses
    ADD CONSTRAINT project_expenses_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_expenses project_expenses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_expenses
    ADD CONSTRAINT project_expenses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: project_files project_files_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_files
    ADD CONSTRAINT project_files_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_members project_members_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: project_members project_members_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_members project_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: project_milestones project_milestones_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones
    ADD CONSTRAINT project_milestones_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: project_milestones project_milestones_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones
    ADD CONSTRAINT project_milestones_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_tasks project_tasks_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: project_tasks project_tasks_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: project_tasks project_tasks_milestone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_milestone_id_foreign FOREIGN KEY (milestone_id) REFERENCES public.project_milestones(id) ON DELETE SET NULL;


--
-- Name: project_tasks project_tasks_parent_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_parent_task_id_foreign FOREIGN KEY (parent_task_id) REFERENCES public.project_tasks(id) ON DELETE CASCADE;


--
-- Name: project_tasks project_tasks_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_templates project_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_templates
    ADD CONSTRAINT project_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: project_time_entries project_time_entries_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_time_entries
    ADD CONSTRAINT project_time_entries_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_time_entries project_time_entries_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_time_entries
    ADD CONSTRAINT project_time_entries_task_id_foreign FOREIGN KEY (task_id) REFERENCES public.project_tasks(id) ON DELETE SET NULL;


--
-- Name: project_time_entries project_time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_time_entries
    ADD CONSTRAINT project_time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: projects projects_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: projects projects_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: projects projects_manager_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_manager_id_foreign FOREIGN KEY (manager_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: quick_action_favorites quick_action_favorites_custom_quick_action_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites
    ADD CONSTRAINT quick_action_favorites_custom_quick_action_id_foreign FOREIGN KEY (custom_quick_action_id) REFERENCES public.custom_quick_actions(id) ON DELETE CASCADE;


--
-- Name: quick_action_favorites quick_action_favorites_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quick_action_favorites
    ADD CONSTRAINT quick_action_favorites_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: quote_approvals quote_approvals_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_approvals
    ADD CONSTRAINT quote_approvals_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: quote_approvals quote_approvals_quote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_approvals
    ADD CONSTRAINT quote_approvals_quote_id_foreign FOREIGN KEY (quote_id) REFERENCES public.quotes(id) ON DELETE CASCADE;


--
-- Name: quote_approvals quote_approvals_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_approvals
    ADD CONSTRAINT quote_approvals_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: quote_invoice_conversions quote_invoice_conversions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_invoice_conversions
    ADD CONSTRAINT quote_invoice_conversions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: quote_templates quote_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_templates
    ADD CONSTRAINT quote_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: quote_templates quote_templates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_templates
    ADD CONSTRAINT quote_templates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: quote_versions quote_versions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions
    ADD CONSTRAINT quote_versions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: quote_versions quote_versions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions
    ADD CONSTRAINT quote_versions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: quote_versions quote_versions_quote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quote_versions
    ADD CONSTRAINT quote_versions_quote_id_foreign FOREIGN KEY (quote_id) REFERENCES public.quotes(id) ON DELETE CASCADE;


--
-- Name: quotes quotes_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes
    ADD CONSTRAINT quotes_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: quotes quotes_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes
    ADD CONSTRAINT quotes_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: quotes quotes_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.quotes
    ADD CONSTRAINT quotes_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: rate_cards rate_cards_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rate_cards
    ADD CONSTRAINT rate_cards_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: rate_cards rate_cards_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rate_cards
    ADD CONSTRAINT rate_cards_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: recurring recurring_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring
    ADD CONSTRAINT recurring_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: recurring recurring_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring
    ADD CONSTRAINT recurring_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: recurring recurring_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring
    ADD CONSTRAINT recurring_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: recurring_invoices recurring_invoices_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_invoices
    ADD CONSTRAINT recurring_invoices_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: recurring_tickets recurring_tickets_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_tickets
    ADD CONSTRAINT recurring_tickets_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: recurring_tickets recurring_tickets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_tickets
    ADD CONSTRAINT recurring_tickets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: recurring_tickets recurring_tickets_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recurring_tickets
    ADD CONSTRAINT recurring_tickets_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.ticket_templates(id) ON DELETE SET NULL;


--
-- Name: refund_requests refund_requests_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: refund_requests refund_requests_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: refund_requests refund_requests_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: refund_transactions refund_transactions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_transactions
    ADD CONSTRAINT refund_transactions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: report_exports report_exports_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_exports
    ADD CONSTRAINT report_exports_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: report_exports report_exports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_exports
    ADD CONSTRAINT report_exports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: report_metrics report_metrics_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_metrics
    ADD CONSTRAINT report_metrics_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: report_subscriptions report_subscriptions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_subscriptions
    ADD CONSTRAINT report_subscriptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: report_subscriptions report_subscriptions_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_subscriptions
    ADD CONSTRAINT report_subscriptions_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.report_templates(id) ON DELETE CASCADE;


--
-- Name: report_subscriptions report_subscriptions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_subscriptions
    ADD CONSTRAINT report_subscriptions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: report_templates report_templates_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates
    ADD CONSTRAINT report_templates_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: report_templates report_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates
    ADD CONSTRAINT report_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: revenue_metrics revenue_metrics_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revenue_metrics
    ADD CONSTRAINT revenue_metrics_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: rmm_client_mappings rmm_client_mappings_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT rmm_client_mappings_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: rmm_client_mappings rmm_client_mappings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT rmm_client_mappings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: rmm_client_mappings rmm_client_mappings_integration_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_client_mappings
    ADD CONSTRAINT rmm_client_mappings_integration_id_foreign FOREIGN KEY (integration_id) REFERENCES public.rmm_integrations(id) ON DELETE CASCADE;


--
-- Name: rmm_integrations rmm_integrations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rmm_integrations
    ADD CONSTRAINT rmm_integrations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: saved_reports saved_reports_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_reports
    ADD CONSTRAINT saved_reports_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: saved_reports saved_reports_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_reports
    ADD CONSTRAINT saved_reports_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.report_templates(id) ON DELETE CASCADE;


--
-- Name: saved_reports saved_reports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_reports
    ADD CONSTRAINT saved_reports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: service_tax_rates service_tax_rates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_tax_rates
    ADD CONSTRAINT service_tax_rates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: services services_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: services services_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: settings settings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: settings_configurations settings_configurations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings_configurations
    ADD CONSTRAINT settings_configurations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: settings_configurations settings_configurations_last_modified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings_configurations
    ADD CONSTRAINT settings_configurations_last_modified_by_foreign FOREIGN KEY (last_modified_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: shifts shifts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: slas slas_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slas
    ADD CONSTRAINT slas_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: subsidiary_permissions subsidiary_permissions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: subsidiary_permissions subsidiary_permissions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: subsidiary_permissions subsidiary_permissions_grantee_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_grantee_company_id_foreign FOREIGN KEY (grantee_company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: subsidiary_permissions subsidiary_permissions_granter_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_granter_company_id_foreign FOREIGN KEY (granter_company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: subsidiary_permissions subsidiary_permissions_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: subsidiary_permissions subsidiary_permissions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsidiary_permissions
    ADD CONSTRAINT subsidiary_permissions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: suspicious_login_attempts suspicious_login_attempts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suspicious_login_attempts
    ADD CONSTRAINT suspicious_login_attempts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: suspicious_login_attempts suspicious_login_attempts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suspicious_login_attempts
    ADD CONSTRAINT suspicious_login_attempts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tags tags_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: task_checklist_items task_checklist_items_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_checklist_items
    ADD CONSTRAINT task_checklist_items_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: task_checklist_items task_checklist_items_completed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_checklist_items
    ADD CONSTRAINT task_checklist_items_completed_by_foreign FOREIGN KEY (completed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: task_checklist_items task_checklist_items_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_checklist_items
    ADD CONSTRAINT task_checklist_items_task_id_foreign FOREIGN KEY (task_id) REFERENCES public.project_tasks(id) ON DELETE CASCADE;


--
-- Name: task_dependencies task_dependencies_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies
    ADD CONSTRAINT task_dependencies_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: task_dependencies task_dependencies_depends_on_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies
    ADD CONSTRAINT task_dependencies_depends_on_task_id_foreign FOREIGN KEY (depends_on_task_id) REFERENCES public.project_tasks(id) ON DELETE CASCADE;


--
-- Name: task_dependencies task_dependencies_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_dependencies
    ADD CONSTRAINT task_dependencies_task_id_foreign FOREIGN KEY (task_id) REFERENCES public.project_tasks(id) ON DELETE CASCADE;


--
-- Name: task_watchers task_watchers_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers
    ADD CONSTRAINT task_watchers_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: task_watchers task_watchers_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers
    ADD CONSTRAINT task_watchers_task_id_foreign FOREIGN KEY (task_id) REFERENCES public.project_tasks(id) ON DELETE CASCADE;


--
-- Name: task_watchers task_watchers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.task_watchers
    ADD CONSTRAINT task_watchers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tax_api_query_cache tax_api_query_cache_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_query_cache
    ADD CONSTRAINT tax_api_query_cache_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tax_api_settings tax_api_settings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_api_settings
    ADD CONSTRAINT tax_api_settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tax_calculations tax_calculations_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tax_calculations tax_calculations_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tax_calculations tax_calculations_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tax_calculations tax_calculations_validated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_calculations
    ADD CONSTRAINT tax_calculations_validated_by_foreign FOREIGN KEY (validated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tax_exemptions tax_exemptions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_exemptions
    ADD CONSTRAINT tax_exemptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tax_exemptions tax_exemptions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_exemptions
    ADD CONSTRAINT tax_exemptions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tax_exemptions tax_exemptions_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_exemptions
    ADD CONSTRAINT tax_exemptions_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tax_jurisdictions tax_jurisdictions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_jurisdictions
    ADD CONSTRAINT tax_jurisdictions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tax_profiles tax_profiles_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_profiles
    ADD CONSTRAINT tax_profiles_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: taxes taxes_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes
    ADD CONSTRAINT taxes_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_assignments ticket_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id);


--
-- Name: ticket_assignments ticket_assignments_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id);


--
-- Name: ticket_assignments ticket_assignments_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_assignments ticket_assignments_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_calendar_events ticket_calendar_events_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_calendar_events
    ADD CONSTRAINT ticket_calendar_events_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_calendar_events ticket_calendar_events_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_calendar_events
    ADD CONSTRAINT ticket_calendar_events_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_comment_attachments ticket_comment_attachments_ticket_comment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comment_attachments
    ADD CONSTRAINT ticket_comment_attachments_ticket_comment_id_foreign FOREIGN KEY (ticket_comment_id) REFERENCES public.ticket_comments(id) ON DELETE CASCADE;


--
-- Name: ticket_priority_queue ticket_priority_queue_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queue
    ADD CONSTRAINT ticket_priority_queue_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_priority_queue ticket_priority_queue_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queue
    ADD CONSTRAINT ticket_priority_queue_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_priority_queues ticket_priority_queues_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queues
    ADD CONSTRAINT ticket_priority_queues_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_priority_queues ticket_priority_queues_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priority_queues
    ADD CONSTRAINT ticket_priority_queues_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_ratings ticket_ratings_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings
    ADD CONSTRAINT ticket_ratings_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ticket_ratings ticket_ratings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings
    ADD CONSTRAINT ticket_ratings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_ratings ticket_ratings_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings
    ADD CONSTRAINT ticket_ratings_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_ratings ticket_ratings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_ratings
    ADD CONSTRAINT ticket_ratings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_replies ticket_replies_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_replies
    ADD CONSTRAINT ticket_replies_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_replies ticket_replies_replied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_replies
    ADD CONSTRAINT ticket_replies_replied_by_foreign FOREIGN KEY (replied_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_replies ticket_replies_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_replies
    ADD CONSTRAINT ticket_replies_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_status_transitions ticket_status_transitions_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_status_transitions
    ADD CONSTRAINT ticket_status_transitions_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_templates ticket_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_templates
    ADD CONSTRAINT ticket_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_time_entries ticket_time_entries_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_time_entries
    ADD CONSTRAINT ticket_time_entries_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_time_entries ticket_time_entries_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_time_entries
    ADD CONSTRAINT ticket_time_entries_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_time_entries ticket_time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_time_entries
    ADD CONSTRAINT ticket_time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_watchers ticket_watchers_added_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT ticket_watchers_added_by_foreign FOREIGN KEY (added_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_watchers ticket_watchers_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT ticket_watchers_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: ticket_watchers ticket_watchers_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT ticket_watchers_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_watchers ticket_watchers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_watchers
    ADD CONSTRAINT ticket_watchers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_workflows ticket_workflows_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_workflows
    ADD CONSTRAINT ticket_workflows_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_closed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: time_entries time_entries_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: time_entry_templates time_entry_templates_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_templates
    ADD CONSTRAINT time_entry_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: time_off_requests time_off_requests_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_off_requests
    ADD CONSTRAINT time_off_requests_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: time_off_requests time_off_requests_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_off_requests
    ADD CONSTRAINT time_off_requests_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_off_requests time_off_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_off_requests
    ADD CONSTRAINT time_off_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: trusted_devices trusted_devices_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trusted_devices
    ADD CONSTRAINT trusted_devices_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: trusted_devices trusted_devices_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trusted_devices
    ADD CONSTRAINT trusted_devices_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: usage_alerts usage_alerts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_alerts
    ADD CONSTRAINT usage_alerts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: usage_alerts usage_alerts_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_alerts
    ADD CONSTRAINT usage_alerts_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: usage_buckets usage_buckets_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_buckets
    ADD CONSTRAINT usage_buckets_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: usage_buckets usage_buckets_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_buckets
    ADD CONSTRAINT usage_buckets_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: usage_pools usage_pools_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_pools
    ADD CONSTRAINT usage_pools_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: usage_records usage_records_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_records
    ADD CONSTRAINT usage_records_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: usage_tiers usage_tiers_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_tiers
    ADD CONSTRAINT usage_tiers_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: user_clients user_clients_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_clients
    ADD CONSTRAINT user_clients_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: user_clients user_clients_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_clients
    ADD CONSTRAINT user_clients_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_dashboard_configs user_dashboard_configs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_dashboard_configs
    ADD CONSTRAINT user_dashboard_configs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: user_dashboard_configs user_dashboard_configs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_dashboard_configs
    ADD CONSTRAINT user_dashboard_configs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_favorite_clients user_favorite_clients_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_favorite_clients
    ADD CONSTRAINT user_favorite_clients_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: user_favorite_clients user_favorite_clients_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_favorite_clients
    ADD CONSTRAINT user_favorite_clients_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_settings user_settings_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings
    ADD CONSTRAINT user_settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: user_settings user_settings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings
    ADD CONSTRAINT user_settings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_widget_instances user_widget_instances_dashboard_widget_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_widget_instances
    ADD CONSTRAINT user_widget_instances_dashboard_widget_id_foreign FOREIGN KEY (dashboard_widget_id) REFERENCES public.dashboard_widgets(id) ON DELETE CASCADE;


--
-- Name: user_widget_instances user_widget_instances_user_dashboard_config_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_widget_instances
    ADD CONSTRAINT user_widget_instances_user_dashboard_config_id_foreign FOREIGN KEY (user_dashboard_config_id) REFERENCES public.user_dashboard_configs(id) ON DELETE CASCADE;


--
-- Name: users users_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: vendors vendors_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors
    ADD CONSTRAINT vendors_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: vendors vendors_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors
    ADD CONSTRAINT vendors_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: vendors vendors_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors
    ADD CONSTRAINT vendors_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: widget_data_cache widget_data_cache_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_data_cache
    ADD CONSTRAINT widget_data_cache_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict o0WKrvcOIrSR7abqcc4IzEk1SJVfnhJjFzP9AAUC16JpR8RXn502IIwbchx5yt0

--
-- PostgreSQL database dump
--

\restrict 4B3eT6uivuMxf5UxT3Wi4iyaPWOmnwdYDYaaU89rb9uHBacJ0Ibpbc7kyuCT5eq

-- Dumped from database version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2024_01_01_000001_create_companies_table	1
2	2024_01_01_100002_create_users_table	1
3	2024_01_01_100003_create_user_settings_table	1
4	2024_01_01_100004_create_user_roles_table	1
5	2024_01_01_100005_create_clients_table	1
6	2024_01_01_100006_create_categories_table	1
7	2024_01_01_100007_create_vendors_table	1
8	2024_01_01_100008_create_locations_table	1
9	2024_01_01_100009_create_contacts_table	1
10	2024_01_01_100010_create_networks_table	1
11	2024_01_01_100011_create_assets_table	1
12	2024_01_01_100012_create_projects_table	1
13	2024_01_01_100013_create_accounts_table	1
14	2024_01_01_100014_create_taxes_table	1
15	2024_01_01_100015_create_quotes_table	1
16	2024_01_01_100016_create_recurring_table	1
17	2024_01_01_100017_create_invoices_table	1
18	2024_01_01_100018_create_products_table	1
19	2024_01_01_100019_create_invoice_items_table	1
20	2024_01_01_100020_create_tickets_table	1
21	2024_01_01_100021_create_ticket_replies_table	1
22	2024_01_01_100022_create_audit_logs_table	1
23	2024_01_01_100023_create_settings_table	1
24	2024_01_01_100024_create_media_table	1
25	2024_01_01_100025_create_asset_maintenance_table	1
26	2024_01_01_100026_create_asset_warranties_table	1
27	2024_01_01_100027_create_asset_depreciations_table	1
28	2024_01_01_100028_create_subscription_plans_table	1
29	2024_01_01_100029_create_payments_table	1
30	2024_01_01_100030_create_ticket_time_entries_table	1
31	2024_01_01_100031_create_sessions_table	1
32	2024_01_01_100033_create_cache_table	1
33	2024_01_01_100034_create_cache_locks_table	1
34	2024_01_01_100035_create_client_certificates_table	1
35	2024_01_01_100036_create_client_racks_table	1
36	2024_01_01_100037_create_client_domains_table	1
37	2024_01_01_100038_create_client_calendar_events_table	1
38	2024_01_01_100039_create_client_recurring_invoices_table	1
39	2024_01_01_100040_create_client_quotes_table	1
40	2024_01_01_100041_create_client_trips_table	1
41	2024_01_01_100043_create_expenses_table	1
42	2024_01_01_100045_create_tags_table	1
43	2024_01_01_100046_create_client_tags_table	1
44	2024_01_01_100047_create_quote_templates_table	1
45	2024_01_01_100048_create_account_holds_table	1
46	2024_01_01_100049_create_analytics_snapshots_table	1
47	2024_01_01_100050_create_auto_payments_table	1
48	2024_01_01_100051_create_cash_flow_projections_table	1
49	2024_01_01_100052_create_client_portal_sessions_table	1
50	2024_01_01_100053_create_client_portal_users_table	1
51	2024_01_01_100054_create_collection_notes_table	1
52	2024_01_01_100055_create_compliance_checks_table	1
53	2024_01_01_100056_create_compliance_requirements_table	1
54	2024_01_01_100057_create_credit_applications_table	1
55	2024_01_01_100058_create_credit_note_approvals_table	1
56	2024_01_01_100059_create_credit_note_items_table	1
57	2024_01_01_100060_create_credit_notes_table	1
58	2024_01_01_100061_create_dunning_actions_table	1
59	2024_01_01_100062_create_dunning_campaigns_table	1
60	2024_01_01_100063_create_dunning_sequences_table	1
61	2024_01_01_100064_create_financial_reports_table	1
62	2024_01_01_100065_create_kpi_calculations_table	1
63	2024_01_01_100066_create_payment_plans_table	1
64	2024_01_01_100067_create_quote_invoice_conversions_table	1
65	2024_01_01_100068_create_recurring_invoices_table	1
66	2024_01_01_100069_create_refund_requests_table	1
67	2024_01_01_100070_create_refund_transactions_table	1
68	2024_01_01_100071_create_revenue_metrics_table	1
69	2024_01_01_100073_create_tax_exemptions_table	1
70	2024_01_01_100074_create_usage_alerts_table	1
71	2024_01_01_100075_create_usage_buckets_table	1
72	2024_01_01_100076_create_usage_pools_table	1
73	2024_01_01_100077_create_usage_records_table	1
74	2024_01_01_100078_create_usage_tiers_table	1
75	2024_01_01_100079_create_client_contacts_table	1
76	2024_01_01_100080_create_client_addresses_table	1
77	2024_01_01_100081_create_client_documents_table	1
78	2024_01_01_100082_create_client_files_table	1
79	2024_01_01_100083_create_client_licenses_table	1
80	2024_01_01_100084_create_client_credentials_table	1
81	2024_01_01_100085_create_client_networks_table	1
82	2024_01_01_100086_create_client_services_table	1
83	2024_01_01_100087_create_client_vendors_table	1
84	2024_01_01_100088_create_client_it_documentation_table	1
85	2024_01_01_100089_create_ticket_templates_table	1
86	2024_01_01_100090_create_recurring_tickets_table	1
87	2024_01_01_100091_create_ticket_workflows_table	1
88	2024_01_01_100092_create_ticket_status_transitions_table	1
89	2024_01_01_100094_create_ticket_priority_queue_table	1
90	2024_01_01_100095_create_ticket_calendar_events_table	1
91	2024_01_01_100200_create_tax_jurisdictions_table	1
92	2024_01_01_100201_create_tax_api_query_cache_table	1
93	2025_01_01_000002_create_services_table	1
94	2025_01_01_000003_create_pricing_rules_table	1
95	2025_01_01_000004_create_product_bundles_table	1
96	2025_08_12_100001_create_dashboard_widgets_table	1
97	2025_08_12_100002_create_user_dashboard_configs_table	1
98	2025_08_12_100003_create_user_widget_instances_table	1
99	2025_08_12_100004_create_dashboard_presets_table	1
100	2025_08_12_100005_create_widget_data_cache_table	1
101	2025_08_12_100006_create_dashboard_metrics_table	1
102	2025_08_12_100007_create_dashboard_activity_logs_table	1
103	2025_08_13_000001_create_contracts_table	1
104	2025_08_13_000002_create_contract_milestones_table	1
105	2025_08_13_000003_create_contract_signatures_table	1
106	2025_08_13_000004_create_contract_approvals_table	1
107	2025_08_13_000005_create_contract_invoice_pivot_table	1
108	2025_08_13_000520_create_slas_table	1
109	2025_08_13_051221_create_bouncer_tables	1
110	2025_08_13_235346_create_quote_versions_table	1
111	2025_08_14_024454_create_company_customizations_table	1
112	2025_08_14_175119_create_portal_notifications_table	1
113	2025_08_14_180718_create_ticket_watchers_table	1
114	2025_08_14_185422_create_rmm_integrations_table	1
115	2025_08_14_192904_create_personal_access_tokens_table	1
116	2025_08_14_195441_create_rmm_client_mappings_table	1
117	2025_08_14_210213_create_jobs_table	1
118	2025_08_14_210225_create_failed_jobs_table	1
119	2025_08_14_211725_create_device_mappings_table	1
120	2025_08_14_215626_create_ticket_priority_queues_table	1
121	2025_08_14_220247_create_ticket_assignments_table	1
122	2025_08_14_221000_create_contract_templates_table	1
123	2025_08_14_221001_create_contract_asset_assignments_table	1
124	2025_08_14_221002_create_contract_contact_assignments_table	1
125	2025_08_14_221003_create_contract_billing_calculations_table	1
126	2025_08_14_231150_create_time_entry_templates_table	1
127	2025_08_14_235032_create_contract_components_table	1
128	2025_08_14_235050_create_contract_component_assignments_table	1
129	2025_08_14_235104_create_contract_versions_table	1
130	2025_08_14_235124_create_contract_negotiations_table	1
131	2025_08_14_235147_create_contract_comments_table	1
132	2025_08_15_160001_create_lead_sources_table	1
133	2025_08_15_160002_create_leads_table	1
134	2025_08_15_160003_create_lead_activities_table	1
135	2025_08_15_160004_create_marketing_campaigns_table	1
136	2025_08_15_160005_create_campaign_sequences_table	1
137	2025_08_15_160006_create_campaign_enrollments_table	1
138	2025_08_15_160007_create_email_tracking_table	1
139	2025_08_15_160008_create_attribution_touchpoints_table	1
140	2025_08_15_160009_create_conversion_events_table	1
141	2025_08_16_043138_create_quote_approvals_table	1
142	2025_08_17_032616_create_ip_lookup_logs_table	1
143	2025_08_17_032620_create_suspicious_login_attempts_table	1
144	2025_08_17_032624_create_trusted_devices_table	1
145	2025_08_18_045119_create_scheduler_coordination_table	1
146	2025_08_18_192400_create_documents_table	1
147	2025_08_18_192454_create_files_table	1
148	2025_08_18_210735_create_contract_schedules_table	1
149	2025_08_18_212929_create_contract_amendments_table	1
150	2025_08_19_155318_create_activity_log_table	1
151	2025_08_19_205458_create_contract_clauses_table	1
152	2025_08_19_205513_create_contract_template_clauses_table	1
153	2025_08_22_000001_create_contract_navigation_items_table	1
154	2025_08_22_000002_create_contract_menu_sections_table	1
155	2025_08_22_000003_create_contract_type_definitions_table	1
156	2025_08_22_000004_create_contract_field_definitions_table	1
157	2025_08_22_000005_create_contract_form_sections_table	1
158	2025_08_22_000006_create_contract_type_form_mappings_table	1
159	2025_08_22_000007_create_contract_view_definitions_table	1
160	2025_08_22_000008_create_contract_list_configurations_table	1
161	2025_08_22_000009_create_contract_detail_configurations_table	1
162	2025_08_22_000010_create_contract_dashboard_widgets_table	1
163	2025_08_22_000011_create_contract_status_definitions_table	1
164	2025_08_22_000012_create_contract_status_transitions_table	1
165	2025_08_22_000013_create_contract_billing_model_definitions_table	1
166	2025_08_22_000014_create_contract_configurations_table	1
167	2025_08_22_000015_create_contract_action_buttons_table	1
168	2025_08_25_120002_create_company_hierarchies_table	1
169	2025_08_25_120003_create_subsidiary_permissions_table	1
170	2025_08_25_120004_create_cross_company_users_table	1
171	2025_09_06_043924_create_user_favorite_clients_table	1
172	2025_09_08_002520_create_ticket_ratings_table	1
173	2025_09_08_233435_create_ticket_comments_table	1
174	2025_09_10_033633_create_user_clients_table	1
175	2025_09_10_164200_create_project_milestones_table	1
176	2025_09_10_164250_create_project_files_table	1
177	2025_09_10_164300_create_project_tasks_table	1
178	2025_09_10_164310_create_project_members_table	1
179	2025_09_10_164320_create_project_templates_table	1
180	2025_09_10_164345_create_project_comments_table	1
181	2025_09_10_164400_create_task_dependencies_table	1
182	2025_09_10_164410_create_task_watchers_table	1
183	2025_09_10_164420_create_task_checklist_items_table	1
184	2025_09_10_172552_create_project_time_entries_table	1
185	2025_09_10_172632_create_project_expenses_table	1
186	2025_09_11_182733_create_communication_logs_table	1
187	2025_09_11_190937_create_email_accounts_table	1
188	2025_09_11_190937_create_email_folders_table	1
189	2025_09_11_190938_create_email_messages_table	1
190	2025_09_11_190939_create_email_attachments_table	1
191	2025_09_11_190939_create_email_signatures_table	1
192	2025_09_12_022239_create_oauth_states_table	1
193	2025_09_14_212900_create_ticket_comment_attachments_table	1
194	2025_09_15_000001_create_company_subscriptions_table	1
195	2025_09_15_225032_create_payment_methods_table	1
196	2025_09_17_202506_create_physical_mail_contacts_table	1
197	2025_09_17_202507_create_physical_mail_templates_table	1
198	2025_09_17_202508_create_physical_mail_bank_accounts_table	1
199	2025_09_17_202509_create_physical_mail_return_envelopes_table	1
200	2025_09_17_202510_create_physical_mail_letters_table	1
201	2025_09_17_202511_create_physical_mail_postcards_table	1
202	2025_09_17_202512_create_physical_mail_cheques_table	1
203	2025_09_17_202513_create_physical_mail_self_mailers_table	1
204	2025_09_17_202514_create_physical_mail_orders_table	1
205	2025_09_17_202515_create_physical_mail_webhooks_table	1
206	2025_09_17_234000_create_physical_mail_settings_table	1
207	2025_09_18_154411_create_time_entries_table	1
208	2025_09_25_193939_create_mail_queue_table	1
209	2025_09_25_195105_create_company_mail_settings_table	1
210	2025_09_25_201814_create_settings_configurations_table	1
211	2025_09_26_191808_create_custom_quick_actions_table	1
212	2025_10_02_160448_create_rate_cards_table	1
213	2025_10_02_163159_create_notification_preferences_table	1
214	2025_10_02_163322_create_in_app_notifications_table	1
215	2025_10_02_214139_create_notifications_table	1
216	2025_10_13_000002_create_report_templates_table	1
217	2025_10_13_000003_create_saved_reports_table	1
218	2025_10_13_000004_create_report_exports_table	1
219	2025_10_13_000005_create_report_subscriptions_table	1
220	2025_10_13_000006_create_report_metrics_table	1
221	2025_10_13_000007_create_kpi_targets_table	1
222	2025_10_14_174816_fix_categories_type_column_to_json	1
223	2025_10_15_154025_create_client_credits_table	1
224	2025_10_15_154025_create_payment_applications_table	1
225	2025_10_15_154026_create_client_credit_applications_table	1
226	2025_10_15_154027_update_payments_table_for_flexible_applications	1
227	2025_10_16_000000_fix_polymorphic_model_namespaces	1
228	2025_10_20_181805_add_project_id_to_invoices_table	1
229	2025_10_21_200000_create_email_templates_table	1
230	2025_10_23_151942_add_hr_settings_to_settings_table	1
231	2025_10_23_151943_create_shifts_table	1
232	2025_10_23_151944_create_pay_periods_table	1
233	2025_10_23_151945_create_employee_time_entries_table	1
234	2025_10_23_151946_create_employee_schedules_table	1
235	2025_10_23_151947_create_time_off_requests_table	1
236	2025_10_23_171237_create_hr_settings_overrides_table	1
237	2025_10_24_000001_add_missing_columns_to_ticket_time_entries	1
238	2025_10_24_000002_add_name_to_notification_preferences	1
239	2025_10_24_170510_add_employment_compensation_to_users	1
240	2025_10_25_170739_add_metadata_to_ticket_time_entries	1
241	2025_10_25_171034_fix_start_time_columns_in_ticket_time_entries	1
242	2025_10_25_195936_create_tax_profiles_table	1
243	2025_10_25_195937_create_permission_groups_table	1
244	2025_10_25_195937_create_tax_api_settings_table	1
245	2025_10_25_195937_create_tax_calculations_table	1
246	2025_10_25_195939_create_product_tax_data_table	1
247	2025_10_25_195940_create_service_tax_rates_table	1
248	2025_10_25_202501_add_missing_foreign_keys_to_tables	1
249	2025_10_25_203333_add_remaining_missing_columns	1
250	2025_10_25_203828_add_final_missing_columns_to_tables	1
251	2025_10_25_204237_add_is_active_and_status_columns	1
252	2025_10_25_204906_add_activation_timestamps_to_auto_payments	1
253	2025_10_25_205313_add_missing_columns_to_client_documents	1
254	2025_10_25_210239_add_missing_columns_to_compliance_checks	1
255	2025_10_25_210439_make_name_nullable_in_compliance_checks	1
256	2025_10_25_211626_add_final_batch_of_missing_columns	1
257	2025_10_25_212231_fix_remaining_column_issues	1
258	2025_10_25_212813_add_very_final_missing_columns	1
259	2025_10_25_213501_add_absolutely_final_missing_columns	1
260	2025_10_25_214100_change_credentials_to_text_in_tax_api_settings	1
261	2025_10_25_214627_add_last_missing_columns_i_promise	1
262	2025_10_25_215705_fix_all_remaining_schema_issues	1
263	2025_10_25_220816_add_final_final_columns	1
264	2025_10_25_221951_add_absolutely_last_columns	1
265	2025_10_27_212706_create_notification_logs_table	2
266	2025_10_29_202938_enhance_client_services_table	2
267	2025_10_29_215606_create_push_subscriptions_table	2
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 267, true);


--
-- PostgreSQL database dump complete
--

\unrestrict 4B3eT6uivuMxf5UxT3Wi4iyaPWOmnwdYDYaaU89rb9uHBacJ0Ibpbc7kyuCT5eq

