import { Link, usePage } from '@inertiajs/react'
import { ReactNode } from 'react'
import {
    Activity,
    Calendar,
    Database,
    GitBranch,
    LayoutDashboard,
    Waves,
    Zap,
} from 'lucide-react'
import type { SharedProps } from '@/types'

interface NavItem {
    label: string
    href:  string
    icon:  ReactNode
}

const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard',      href: '/',               icon: <LayoutDashboard className="w-4 h-4" /> },
    { label: 'Services',       href: '/services',       icon: <Database        className="w-4 h-4" /> },
    { label: 'Endpoints',      href: '/endpoints',      icon: <GitBranch       className="w-4 h-4" /> },
    { label: 'Mock Responses', href: '/mock-responses', icon: <Zap             className="w-4 h-4" /> },
    { label: 'Scheduler',      href: '/scheduler',      icon: <Calendar        className="w-4 h-4" /> },
    { label: 'Request Logs',   href: '/logs',           icon: <Activity        className="w-4 h-4" /> },
]

interface AppLayoutProps {
    children: ReactNode
    title?:   string
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const { url, props } = usePage<SharedProps>()
    const { auth, flash } = props

    return (
        <div className="flex h-screen bg-gray-50 overflow-hidden">

            {/* ── Sidebar ── */}
            <aside className="w-60 flex-shrink-0 bg-gray-900 flex flex-col">
                {/* Logo */}
                <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-800">
                    <Waves className="w-6 h-6 text-wave-400" />
                    <span className="text-white font-semibold text-lg tracking-tight">Mockwave</span>
                </div>

                {/* Nav */}
                <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                    {NAV_ITEMS.map((item) => {
                        const active = url === item.href || (item.href !== '/' && url.startsWith(item.href))
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-wave-600 text-white'
                                        : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                ].join(' ')}
                            >
                                {item.icon}
                                {item.label}
                            </Link>
                        )
                    })}
                </nav>

                {/* User */}
                <div className="px-4 py-3 border-t border-gray-800">
                    <p className="text-xs text-gray-500 truncate">{auth.user?.email}</p>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-xs text-gray-400 hover:text-white mt-0.5 transition-colors"
                    >
                        Sign out
                    </Link>
                </div>
            </aside>

            {/* ── Main area ── */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Top bar */}
                {title && (
                    <header className="flex-shrink-0 bg-white border-b border-gray-200 px-6 py-4">
                        <h1 className="text-lg font-semibold text-gray-900">{title}</h1>
                    </header>
                )}

                {/* Flash messages */}
                {(flash.success || flash.error) && (
                    <div className={[
                        'flex-shrink-0 px-6 py-3 text-sm font-medium',
                        flash.success ? 'bg-green-50 text-green-800 border-b border-green-200' : '',
                        flash.error   ? 'bg-red-50 text-red-800 border-b border-red-200'       : '',
                    ].join(' ')}>
                        {flash.success || flash.error}
                    </div>
                )}

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-6">
                    {children}
                </main>
            </div>
        </div>
    )
}
