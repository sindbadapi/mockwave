import AppLayout from '@/Components/layout/AppLayout'
import { Head, Link } from '@inertiajs/react'
import { Activity, Calendar, Database, GitBranch } from 'lucide-react'

interface Props {
    stats: {
        services: number
        endpoints: number
        logs_today: number
        webhooks: number
    }
}

const CARDS = [
    { key: 'services',   label: 'Services',        icon: Database,  href: '/services'  },
    { key: 'endpoints',  label: 'Endpoints',       icon: GitBranch, href: '/endpoints' },
    { key: 'logs_today', label: 'Requests today',  icon: Activity,  href: '/logs'      },
    { key: 'webhooks',   label: 'Active webhooks', icon: Calendar,  href: '/scheduler' },
] as const

export default function Dashboard({ stats }: Props) {
    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {CARDS.map(({ key, label, icon: Icon, href }) => (
                    <Link key={key} href={href}
                        className="rounded-xl border border-gray-200 bg-white p-5 transition-colors hover:border-wave-300">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-500">{label}</span>
                            <Icon className="h-5 w-5 text-wave-500" />
                        </div>
                        <p className="mt-2 text-3xl font-semibold text-gray-900">{stats[key]}</p>
                    </Link>
                ))}
            </div>
        </AppLayout>
    )
}
