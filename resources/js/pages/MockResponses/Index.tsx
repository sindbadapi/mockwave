import AppLayout from '@/Components/layout/AppLayout'
import { useIsAdmin } from '@/hooks/useIsAdmin'
import type { Endpoint, PaginatedResponse } from '@/types'
import { Head, router, useForm } from '@inertiajs/react'
import { Pencil, Trash2, X } from 'lucide-react'
import { ReactNode, useState } from 'react'

interface Props {
    endpoints: PaginatedResponse<Endpoint>
}

const EMPTY_FORM = {
    endpoint_id: 0,
    status_code: 200,
    delay_ms:    0,
    body:        '',
    headers:     '',
}

function stringifyJson(value: Record<string, unknown> | null): string {
    return value ? JSON.stringify(value, null, 2) : ''
}

export default function MockResponsesIndex({ endpoints }: Props) {
    const isAdmin               = useIsAdmin()
    const [open, setOpen]       = useState(false)
    const [editing, setEditing] = useState<Endpoint | null>(null)
    const [jsonError, setJsonError] = useState<string | null>(null)

    const form = useForm(EMPTY_FORM)
    const { data, setData, processing, errors, reset } = form

    const openEdit = (endpoint: Endpoint) => {
        setJsonError(null)
        setEditing(endpoint)
        const mock = endpoint.mock_response
        setData({
            endpoint_id: endpoint.id,
            status_code: mock?.status_code ?? 200,
            delay_ms:    mock?.delay_ms ?? 0,
            body:        stringifyJson(mock?.body ?? null),
            headers:     stringifyJson(mock?.headers ?? null),
        })
        setOpen(true)
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault()

        let body: Record<string, unknown> | null = null
        let headers: Record<string, unknown> | null = null
        try {
            body = data.body.trim() ? JSON.parse(data.body) : null
            headers = data.headers.trim() ? JSON.parse(data.headers) : null
        } catch {
            setJsonError('Body and Headers must be valid JSON.')
            return
        }
        setJsonError(null)

        form.transform((d) => ({ ...d, body, headers }))
        form.post(route('mock-responses.store'), {
            onSuccess: () => { setOpen(false); reset() },
        })
    }

    const destroy = (endpoint: Endpoint) => {
        if (endpoint.mock_response && confirm(`Delete mock response for ${endpoint.method} ${endpoint.path}?`)) {
            router.delete(route('mock-responses.destroy', endpoint.mock_response.id))
        }
    }

    return (
        <AppLayout title="Mock Responses">
            <Head title="Mock Responses" />

            <p className="text-sm text-gray-500 mb-4">
                Configure the canned response returned for each endpoint in <span className="badge-mock">mock</span> mode.
            </p>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            {['Method', 'Path', 'Service', 'Status', 'Delay', 'Mock', ''].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {endpoints.data.map((ep) => (
                            <tr key={ep.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <code className="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">{ep.method}</code>
                                </td>
                                <td className="px-4 py-3 font-mono text-xs text-gray-700 max-w-[220px] truncate">{ep.path}</td>
                                <td className="px-4 py-3 text-gray-600">{ep.service?.name ?? '—'}</td>
                                <td className="px-4 py-3 font-mono text-xs text-gray-600">{ep.mock_response?.status_code ?? '—'}</td>
                                <td className="px-4 py-3 text-gray-500 text-xs">{ep.mock_response ? `${ep.mock_response.delay_ms}ms` : '—'}</td>
                                <td className="px-4 py-3">
                                    <span className={ep.mock_response ? 'badge-active' : 'badge-inactive'}>
                                        {ep.mock_response ? 'configured' : 'none'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    {isAdmin && (
                                        <div className="flex items-center gap-2 justify-end">
                                            <button onClick={() => openEdit(ep)} className="p-1.5 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100">
                                                <Pencil className="w-3.5 h-3.5" />
                                            </button>
                                            {ep.mock_response && (
                                                <button onClick={() => destroy(ep)} className="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50">
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {endpoints.data.length === 0 && (
                            <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No endpoints yet. Create one first.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {open && editing && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="font-semibold text-gray-900">
                                Mock for <code className="text-sm font-mono">{editing.method} {editing.path}</code>
                            </h2>
                            <button onClick={() => setOpen(false)} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={submit} className="px-5 py-4 space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <Field label="Status code" error={errors.status_code}>
                                    <input type="number" value={data.status_code} onChange={e => setData('status_code', Number(e.target.value))}
                                        className="input-base font-mono" min={100} max={599} />
                                </Field>
                                <Field label="Delay (ms)" error={errors.delay_ms}>
                                    <input type="number" value={data.delay_ms} onChange={e => setData('delay_ms', Number(e.target.value))}
                                        className="input-base font-mono" min={0} max={30000} />
                                </Field>
                            </div>
                            <Field label="Body (JSON)" error={errors.body}>
                                <textarea value={data.body} onChange={e => setData('body', e.target.value)}
                                    className="input-base font-mono" rows={5} placeholder='{"result": "ok"}' />
                            </Field>
                            <Field label="Headers (JSON)" error={errors.headers}>
                                <textarea value={data.headers} onChange={e => setData('headers', e.target.value)}
                                    className="input-base font-mono" rows={2} placeholder='{"X-Source": "Mockwave"}' />
                            </Field>

                            {jsonError && <p className="text-xs text-red-500">{jsonError}</p>}

                            <div className="flex gap-3 pt-2">
                                <button type="button" onClick={() => setOpen(false)}
                                    className="flex-1 px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" disabled={processing}
                                    className="flex-1 px-4 py-2 text-sm text-white bg-wave-600 rounded-lg hover:bg-wave-700 transition-colors disabled:opacity-60">
                                    {processing ? 'Saving…' : 'Save mock'}
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
