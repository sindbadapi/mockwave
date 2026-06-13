import AppLayout from '@/Components/layout/AppLayout'
import { useIsAdmin } from '@/hooks/useIsAdmin'
import type { Endpoint, HttpMethod, PaginatedResponse, Service, ServiceMode } from '@/types'
import { Head, router, useForm } from '@inertiajs/react'
import { Pencil, Plus, Trash2, X } from 'lucide-react'
import { ReactNode, useState } from 'react'

type ServiceOption = Pick<Service, 'id' | 'name' | 'slug' | 'mode'>

interface Props {
    endpoints: PaginatedResponse<Endpoint>
    services:  ServiceOption[]
    filters:   { service_id: string | number | null }
}

const METHODS: HttpMethod[] = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'ANY']

const EMPTY_FORM = {
    service_id:    '' as number | '',
    method:        'GET' as HttpMethod,
    path:          '',
    mode_override: '' as '' | ServiceMode,
    proxy_url:     '',
    is_active:     true,
}

export default function EndpointsIndex({ endpoints, services, filters }: Props) {
    const isAdmin               = useIsAdmin()
    const [open, setOpen]       = useState(false)
    const [editing, setEditing] = useState<Endpoint | null>(null)

    const form = useForm(EMPTY_FORM)
    const { data, setData, processing, errors, reset } = form

    const filterByService = (serviceId: string) => {
        router.get(route('endpoints.index'), serviceId ? { service_id: serviceId } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }

    const openCreate = () => { reset(); setEditing(null); setOpen(true) }

    const openEdit = (endpoint: Endpoint) => {
        setEditing(endpoint)
        setData({
            service_id:    endpoint.service_id,
            method:        endpoint.method,
            path:          endpoint.path,
            mode_override: endpoint.mode_override ?? '',
            proxy_url:     endpoint.proxy_url ?? '',
            is_active:     endpoint.is_active,
        })
        setOpen(true)
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        form.transform((d) => ({
            ...d,
            mode_override: d.mode_override === '' ? null : d.mode_override,
            proxy_url:     d.proxy_url === '' ? null : d.proxy_url,
        }))

        const onSuccess = () => { setOpen(false); reset() }
        if (editing) {
            form.put(route('endpoints.update', editing.id), { onSuccess })
        } else {
            form.post(route('endpoints.store'), { onSuccess })
        }
    }

    const destroy = (endpoint: Endpoint) => {
        if (confirm(`Delete endpoint ${endpoint.method} ${endpoint.path}?`)) {
            router.delete(route('endpoints.destroy', endpoint.id))
        }
    }

    return (
        <AppLayout title="Endpoints">
            <Head title="Endpoints" />

            <div className="flex items-center justify-between mb-4 gap-3">
                <select
                    value={filters.service_id ?? ''}
                    onChange={e => filterByService(e.target.value)}
                    className="input-base max-w-xs">
                    <option value="">All services</option>
                    {services.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>

                {isAdmin && (
                    <button onClick={openCreate}
                        className="flex items-center gap-2 px-4 py-2 bg-wave-600 text-white text-sm font-medium rounded-lg hover:bg-wave-700 transition-colors whitespace-nowrap">
                        <Plus className="w-4 h-4" /> New Endpoint
                    </button>
                )}
            </div>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            {['Method', 'Path', 'Service', 'Mode', 'Mock', 'Status', ''].map(h => (
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
                                <td className="px-4 py-3">
                                    <span className={ep.resolved_mode === 'mock' ? 'badge-mock' : 'badge-proxy'}>{ep.resolved_mode}</span>
                                    {ep.mode_override && <span className="ml-1 text-xs text-gray-400">(override)</span>}
                                </td>
                                <td className="px-4 py-3 text-gray-600">{ep.mock_response ? '✓' : '—'}</td>
                                <td className="px-4 py-3">
                                    <span className={ep.is_active ? 'badge-active' : 'badge-inactive'}>
                                        {ep.is_active ? 'active' : 'inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    {isAdmin && (
                                        <div className="flex items-center gap-2 justify-end">
                                            <button onClick={() => openEdit(ep)} className="p-1.5 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100">
                                                <Pencil className="w-3.5 h-3.5" />
                                            </button>
                                            <button onClick={() => destroy(ep)} className="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50">
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {endpoints.data.length === 0 && (
                            <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No endpoints found.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {open && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="font-semibold text-gray-900">{editing ? 'Edit Endpoint' : 'New Endpoint'}</h2>
                            <button onClick={() => setOpen(false)} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={submit} className="px-5 py-4 space-y-4">
                            <Field label="Service" error={errors.service_id}>
                                <select value={data.service_id} onChange={e => setData('service_id', e.target.value ? Number(e.target.value) : '')} className="input-base">
                                    <option value="">Select a service…</option>
                                    {services.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>
                            <div className="grid grid-cols-2 gap-3">
                                <Field label="Method" error={errors.method}>
                                    <select value={data.method} onChange={e => setData('method', e.target.value as HttpMethod)} className="input-base">
                                        {METHODS.map(m => <option key={m} value={m}>{m}</option>)}
                                    </select>
                                </Field>
                                <Field label="Mode override" error={errors.mode_override}>
                                    <select value={data.mode_override} onChange={e => setData('mode_override', e.target.value as '' | ServiceMode)} className="input-base">
                                        <option value="">Inherit</option>
                                        <option value="mock">Mock</option>
                                        <option value="proxy">Proxy</option>
                                    </select>
                                </Field>
                            </div>
                            <Field label="Path" error={errors.path}>
                                <input value={data.path} onChange={e => setData('path', e.target.value)}
                                    className="input-base font-mono" placeholder="/v1/accounts" />
                            </Field>
                            <Field label="Proxy URL (optional)" error={errors.proxy_url}>
                                <input value={data.proxy_url} onChange={e => setData('proxy_url', e.target.value)}
                                    className="input-base" placeholder="https://upstream.example.com" />
                            </Field>
                            <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-wave-600 focus:ring-wave-500" />
                                Active
                            </label>

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
