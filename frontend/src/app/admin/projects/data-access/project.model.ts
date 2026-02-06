export interface Project {
    id?: number;
    name: string;
    description?: string
    goal?: string;
    source_code: string;
    live_demo?: string;
    tech_stack_ids?: number[];
}
