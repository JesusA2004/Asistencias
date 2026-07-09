export type ClientStatus = 'activo' | 'inactivo';
export type EmployeeStatus = 'activo' | 'inactivo' | 'baja';
export type AttendanceStatus = 'presente' | 'falta' | 'descanso' | 'permiso' | 'incapacidad' | 'retardo';
export type AttendanceEventType = 'asistencia' | 'entrada' | 'salida' | 'incidencia' | 'correccion';
export type AttendanceAuditAction = 'creado' | 'actualizado' | 'eliminado' | 'corregido';

export interface Client {
    id: number;
    name: string;
    business_name: string | null;
    rfc: string | null;
    status: ClientStatus;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    service_points?: ServicePoint[];
    employees_count?: number;
}

export interface ServicePoint {
    id: number;
    client_id: number;
    name: string;
    address: string | null;
    status: ClientStatus;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    client?: Client;
    employees_count?: number;
}

export interface Shift {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    work_days: string[] | null;
    tolerance_minutes: number;
    status: ClientStatus;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Employee {
    id: number;
    employee_number: string;
    name: string;
    last_name: string;
    second_last_name: string | null;
    email: string | null;
    phone: string | null;
    status: EmployeeStatus;
    client_id: number | null;
    service_point_id: number | null;
    shift_id: number | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    full_name?: string;
    client?: Client;
    service_point?: ServicePoint;
    shift?: Shift;
}

export interface SupervisorAssignment {
    id: number;
    supervisor_user_id: number;
    client_id: number;
    service_point_id: number | null;
    created_at: string;
    updated_at: string;
    supervisor?: AppUser;
    client?: Client;
    service_point?: ServicePoint | null;
}

export interface Attendance {
    id: number;
    employee_id: number;
    client_id: number;
    service_point_id: number;
    shift_id: number | null;
    supervisor_id: number;
    attendance_date: string;
    status: AttendanceStatus;
    entry_time: string | null;
    exit_time: string | null;
    notes: string | null;
    created_by: number;
    updated_by: number | null;
    deleted_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    employee?: Employee;
    client?: Client;
    service_point?: ServicePoint;
    shift?: Shift | null;
    supervisor?: AppUser;
    creator?: AppUser;
}

export interface AttendanceAudit {
    id: number;
    attendance_id: number;
    action: AttendanceAuditAction;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    reason: string | null;
    changed_by: number;
    created_at: string;
    attendance?: Attendance;
    changer?: AppUser;
}

export interface AppUser {
    id: number;
    name: string;
    email: string;
    roles?: string[];
    permissions?: string[];
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions?: Permission[];
    users_count?: number;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface DashboardAdminStats {
    active_employees: number;
    today_present: number;
    today_absent: number;
    today_late: number;
    compliance_percentage: number;
    active_clients: number;
    pending_captures: number;
}

export interface DashboardSupervisorStats {
    assigned_locations: number;
    pending_captures_today: number;
    captured_today: number;
    absences_today: number;
    lates_today: number;
}
