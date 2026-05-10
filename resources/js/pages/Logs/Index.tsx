import AppLayout from '@/Components/layout/AppLayout'
import { Head, router } from '@inertiajs/react'
import { useState } from 'react'
import { ChevronDown, ChevronRight, Trash2 } from 'lucide-react'
import type { LogMode, PaginatedResponse, RequestLog } from '@/types'

interface Props {
    logs: PaginatedResponse<RequestLog>
}

const MODE_BADGE: Record<LogMode, string> = {
    mock:      'badge-mock',
    proxy:     'badge-proxy',
    not_found: 'badge-inactive',
}

const STATUS_COLOR: Record<string, string> = {
    '2': 'text-green-600',
    '3': 'text-blue-600',
    '4': 'text-orange-600',
    '5': 'text-red-600',
}

function statusColor(code: number): string {
    return STATUS_COLOR[String(code)[0]] ?? 'text-gray-600'
}

export default function LogsIndex({ logs }: Props) {
    const [expanded, setExpanded] = useState<number | null>(null)

    const clearAll = () => {
        if (confirm('Delete all request logs? This cannot be undone.')) {
            router.delete('/api/admin/logs')
        }
    }

    return (
        <AppLayout title="Request Logs">
            <Head title="Request Logs" />

            <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-gray-500">{logs.meta.total} log entries</p>
                <button onClick={clearAll}
                    className="flex items-center gap-2 px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                    <Trash2 className="w-4 h-4" /> Clear all logs
                </button>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            {['', 'Time', 'Method', 'Path', 'Mode', 'Status', 'Duration'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {logs.data.map((log) => {
                            const isOpen    = expanded === log.id
                            const status    = log.response_data?.status ?? null

                            return (
                                <>
                                    <tr key={log.id}
                                        className="hover:bg-gray-50 cursor-pointer"
                                        onClick={() => setExpanded(isOpen ? null : log.id)}>
                                        <td className="px-3 py-3 text-gray-400">
                                            {isOpen ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                                        </td>
                                        <td className="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                            {new Date(log.created_at).toLocaleTimeString()}
                                        </td>
                                        <td className="px-4 py-3">
                                            <code className="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">{log.method}</code>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs text-gray-700 max-w-[240px] truncate">{log.path}</td>
                                        <td className="px-4 py-3">
                                            <span className={MODE_BADGE[log.mode_used]}>{log.mode_used}</span>
                                        </td>
                                        <td className={`px-4 py-3 font-mono text-xs font-semibold ${status ? statusColor(status) : 'text-gray-400'}`}>
                                            {status ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-500 text-xs">{log.duration_ms}ms</td>
                                    </tr>

                                    {/* Expanded detail row */}
                                    {isOpen && (
                                        <tr key={`${log.id}-detail`} className="bg-gray-50">
                                            <td colSpan={7} className="px-6 py-4">
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <p className="text-xs font-semibold text-gray-500 uppercase mb-1">Request Body</p>
                                                        <pre className="json-viewer">{log.request_data?.body || '(empty)'}</pre>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-semibold text-gray-500 uppercase mb-1">Response Body</p>
                                                        <pre className="json-viewer">{log.response_data?.body || '(empty)'}</pre>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </>
                            )
                        })}
                        {logs.data.length === 0 && (
                            <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No logs yet. Send a request to the gateway.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    )
}
