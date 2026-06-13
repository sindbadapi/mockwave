import AppLayout from '@/Components/layout/AppLayout'
import { useIsAdmin } from '@/hooks/useIsAdmin'
import type { HttpMethod, PaginatedResponse, ScheduledWebhook } from '@/types'
import { Head, router, useForm } from '@inertiajs/react'
import { Pencil, Plus, Trash2, X } from 'lucide-react'
import { ReactNode, useState } from 'react'

interface Props {
    webhooks: PaginatedResponse<ScheduledWebhook>
}

const METHODS: HttpMethod[] = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']

const EMPTY_FORM = {
    name:            '',
    target_url:      '',
    method:          'POST' as HttpMethod,
    cron_expression: '*/15 * * * *',
    is_active:       true,
    payload:         '',
    headers:         '',
}

function stringifyJson(value: Record<string, unknown> | null): string {
    return value ? JSON.stringify(value, null, 2) : ''
}

export default function SchedulerIndex({ webhooks }: Props) {
    const isAdmin               = useIsAdmin()
    const [open, setOpen]       = useState(false)
    const [editing, setEditing] = useState<ScheduledWebhook | null>(null)
    const [jsonError, setJsonError] = useState<string | null>(null)

    const form = useForm(EMPTY_FORM)
    const { data, setData, processing, errors, reset } = form

    const openCreate = () => { reset(); setJsonError(null); setEditing(null); setOpen(true) }

    const openEdit = (webhook: ScheduledWebhook) => {
        setJsonError(null)
        setEditing(webhook)
        setData({
            name:            webhook.name,
            target_url:      webhook.target_url,
            method:          webhook.method,
            cron_expression: webhook.cron_expression,
            is_active:       webhook.is_active,
            payload:         stringifyJson(webhook.payload),
            headers:         stringifyJson(webhook.headers),
        })
        setOpen(true)
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault()

        let payload: Record<string, unknown> | null = null
        let headers: Record<string, unknown> | null = null
        try {
            payload = data.payload.trim() ? JSON.parse(data.payload) : null
            headers = data.headers.trim() ? JSON.parse(data.headers) : null
        } catch {
            setJsonError('Payload and Headers must be valid JSON.')
            return
        }
        setJsonError(null)

        form.transform((d) => ({ ...d, payload, headers }))

        const onSuccess = () => { setOpen(false); reset() }
        if (editing) {
            form.put(route('scheduler.update', editing.id), { onSuccess })
        } else {
            form.post(route('scheduler.store'), { onSuccess })
        }
    }

    const destroy = (webhook: ScheduledWebhook) => {
        if (confirm(`Delete webhook "${webhook.name}"?`)) {
            router.delete(route('scheduler.destroy', webhook.id))
        }
    }

    return (
        <AppLayout title="Scheduler">
            <Head title="Scheduler" />

            <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-gray-500">{webhooks.meta.total} webhook(s)</p>
                {isAdmin && (
                    <button onClick={openCreate}
                        className="flex items-center gap-2 px-4 py-2 bg-wave-600 text-white text-sm font-medium rounded-lg hover:bg-wave-700 transition-colors">
                        <Plus className="w-4 h-4" /> New Webhook
                    </button>
                )}
            </div>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            {['Name', 'Target URL', 'Method', 'Cron', 'Last run', 'Status', ''].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {webhooks.data.map((w) => (
                            <tr key={w.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{w.name}</td>
                                <td className="px-4 py-3 font-mono text-xs text-gray-600 max-w-[260px] truncate">{w.target_url}</td>
                                <td className="px-4 py-3">
                                    <code className="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{w.method}</code>
                                </td>
                                <td className="px-4 py-3 font-mono text-xs text-gray-600">{w.cron_expression}</td>
                                <td className="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                    {w.last_run_at ? new Date(w.last_run_at).toLocaleString() : '—'}
                                </td>
                                <td className="px-4 py-3">
                                    <span className={w.is_active ? 'badge-active' : 'badge-inactive'}>
                                        {w.is_active ? 'active' : 'inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    {isAdmin && (
                                        <div className="flex items-center gap-2 justify-end">
                                            <button onClick={() => openEdit(w)} className="p-1.5 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100">
                                                <Pencil className="w-3.5 h-3.5" />
                                            </button>
                                            <button onClick={() => destroy(w)} className="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50">
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {webhooks.data.length === 0 && (
                            <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No webhooks yet.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {open && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="font-semibold text-gray-900">{editing ? 'Edit Webhook' : 'New Webhook'}</h2>
                            <button onClick={() => setOpen(false)} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={submit} className="px-5 py-4 space-y-4">
                            <Field label="Name" error={errors.name}>
                                <input value={data.name} onChange={e => setData('name', e.target.value)}
                                    className="input-base" placeholder="Daily sync" />
                            </Field>
                            <Field label="Target URL" error={errors.target_url}>
                                <input value={data.target_url} onChange={e => setData('target_url', e.target.value)}
                                    className="input-base" placeholder="https://example.com/webhook" />
                            </Field>
                            <div className="grid grid-cols-2 gap-3">
                                <Field label="Method" error={errors.method}>
                                    <select value={data.method} onChange={e => setData('method', e.target.value as HttpMethod)} className="input-base">
                                        {METHODS.map(m => <option key={m} value={m}>{m}</option>)}
                                    </select>
                                </Field>
                                <Field label="Cron" error={errors.cron_expression}>
                                    <input value={data.cron_expression} onChange={e => setData('cron_expression', e.target.value)}
                                        className="input-base font-mono" placeholder="*/15 * * * *" />
                                </Field>
                            </div>
                            <Field label="Payload (JSON)" error={errors.payload}>
                                <textarea value={data.payload} onChange={e => setData('payload', e.target.value)}
                                    className="input-base font-mono" rows={3} placeholder='{"event": "ping"}' />
                            </Field>
                            <Field label="Headers (JSON)" error={errors.headers}>
                                <textarea value={data.headers} onChange={e => setData('headers', e.target.value)}
                                    className="input-base font-mono" rows={2} placeholder='{"X-Token": "abc"}' />
                            </Field>
                            <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-wave-600 focus:ring-wave-500" />
                                Active
                            </label>

                            {jsonError && <p className="text-xs text-red-500">{jsonError}</p>}

                            <div className="flex gap-3 pt-2">
                                <button type="button" onClick={() => setOpen(false)}
                                    className="flex-1 px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" disabled={processing}
                                    className="flex-1 px-4 py-2 text-sm text-white bg-wave-600 rounded-lg hover:bg-wave-700 transition-colors disabled:opacity-60">
                                    {processing ? 'Saving…' : editing ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    )
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div>
            <label className="block text-xs font-medium text-gray-700 mb-1">{label}</label>
            {children}
            {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
        </div>
    )
}
