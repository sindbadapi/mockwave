import { usePage } from '@inertiajs/react'
import type { SharedProps } from '@/types'

/** Whether the current authenticated user has the admin role. */
export function useIsAdmin(): boolean {
    return usePage<SharedProps>().props.auth.user?.role === 'admin'
}
