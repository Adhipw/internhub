import type { User } from './user';
import type { Internship } from './internship';

export interface Application {
    id: number;
    user_id: number;
    internship_id: number;
    status: 'pending' | 'reviewing' | 'interviewing' | 'offered' | 'accepted' | 'rejected' | 'withdrawn' | 'onboarding';
    applied_at: string;
    created_at: string;
    updated_at: string;
    created_at_human?: string;
    user: User;
    internship: Internship;
    documents?: ApplicationDocument[];
    logs?: ApplicationLog[];
    hr_notes?: string;
    timeline?: ApplicationTimelineEvent[];
    cover_letter?: string;
    cv_snapshot?: string;
    portfolio_snapshot?: string;
    onboarding_documents?: ApplicationDocument[];
    interviewer_id?: number;
    score?: any;
}

export interface ApplicationTimelineEvent {
    label: string;
    date: string;
    description: string;
}

export interface ApplicationDocument {
    id: number;
    application_id: number;
    type: string;
    file_path: string;
    file_url: string;
    name: string;
    status?: string;
    created_at_human?: string;
    notes?: string;
}

export interface ApplicationLog {
    id: number;
    application_id: number;
    status: string;
    comment?: string;
    created_at: string;
}
