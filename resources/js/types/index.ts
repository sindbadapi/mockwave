// ─── Domain types ────────────────────────────────────────────────────────────

export type ServiceMode = 'mock' | 'proxy'
export type HttpMethod  = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' | 'HEAD' | 'OPTIONS' | 'ANY'
export type LogMode     = 'mock' | 'proxy' | 'not_found'
export type UserRole    = 'admin' | 'user'

export interface Service {
    id:              number
    name:            string
    slug:            string
    base_url:        string | null
    gateway_prefix:  string
    gateway_base_uri: string
    gateway_client_hint: string
    description:     string | null
    mode:            ServiceMode
    is_active:       boolean
    endpoints_count: number
    created_at:      string
    updated_at:      string
}

export interface MockResponse {
    id:          number
    endpoint_id: number
    status_code: number
    body:        Record<string, unknown> | null
    headers:     Record<string, string> | null
    delay_ms:    number
    created_at:  string
    updated_at:  string
}

export interface Endpoint {
    id:            number
    service_id:    number
    service:       Service
    method:        HttpMethod
    path:          string
    mode_override: ServiceMode | null
    resolved_mode: ServiceMode
    proxy_url:     string | null
    is_active:     boolean
    mock_response: MockResponse | null
    created_at:    string
    updated_at:    string
}

export interface ScheduledWebhook {
    id:              number
    name:            string
    target_url:      string
    method:          HttpMethod
    payload:         Record<string, unknown> | null
    headers:         Record<string, string> | null
    cron_expression: string
    is_active:       boolean
    last_run_at:     string | null
    created_at:      string
    updated_at:      string
}

export interface RequestLog {
    id:             number
    endpoint_id:    number | null
    endpoint:       Endpoint | null
    method:         HttpMethod
    path:           string
    request_data:   RequestData | null
    response_data:  ResponseData | null
    mode_used:      LogMode
    duration_ms:    number
    created_at:     string
}

export interface RequestData {
    headers: Record<string, string[]>
    query:   Record<string, string>
    body:    string
}

export interface ResponseData {
    status:  number
    headers: Record<string, string[]>
    body:    string
}

// ─── Pagination wrapper ───────────────────────────────────────────────────────

export interface PaginatedResponse<T> {
    data: T[]
    meta: {
        current_page: number
        last_page:    number
        per_page:     number
        total:        number
    }
    links: {
        first: string | null
        last:  string | null
        prev:  string | null
        next:  string | null
    }
}

// ─── Inertia shared props ─────────────────────────────────────────────────────

export interface SharedProps {
    auth: {
        user: { id: number; name: string; email: string; email_verified_at: string | null; role: UserRole } | null
    }
    flash: {
        success: string | null
        error:   string | null
    }
    [key: string]: unknown
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = SharedProps & T
