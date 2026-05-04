import AppLayout from '@/Components/layout/AppLayout'
import { Head, router, useForm } from '@inertiajs/react'
import { useState } from 'react'
import { Pencil, Plus, Trash2, X } from 'lucide-react'
import type { PaginatedResponse, Service, ServiceMode } from '@/types'

interface Props {
    services: PaginatedResponse<Service>
}

const EMPTY_FORM = {
    name:        '',
    slug:        '',
    base_url:    '',
    description: '',
    mode:        'mock' as ServiceMode,
    is_active:   true,
}

export default function ServicesIndex({ services }: Props) {
    const [open, setOpen]       = useState(false)
    const [editing, setEditing] = useState<Service | null>(null)

    const { data, setData, post, put, processing, errors, reset } = useForm(EMPTY_FORM)

    const openCreate = () => { reset(); setEditing(null); setOpen(true) }

    const openEdit = (service: Service) => {
        setEditing(service)
        setData({
            name:        service.name,
            slug:        service.slug,
            base_url:    service.base_url ?? '',
            description: service.description ?? '',
            mode:        service.mode,
            is_active:   service.is_active,
        })
        setOpen(true)
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        if (editing) {
            put(`/api/admin/services/${editing.id}`, { onSuccess: () => { setOpen(false); reset() } })
        } else {
            post('/api/admin/services', { onSuccess: () => { setOpen(false); reset() } })
        }
    }

    const destroy = (service: Service) => {
        if (confirm(`Delete service "${service.name}"? All endpoints and logs will be removed.`)) {
            router.delete(`/api/admin/services/${service.id}`)
        }
    }

    return (
        <AppLayout title="Services">
            <Head title="Services" />

            {/* ── Header ── */}
            <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-gray-500">{services.meta.total} service(s)</p>
                <button onClick={openCreate}
                    className="flex items-center gap-2 px-4 py-2 bg-wave-600 text-white text-sm font-medium rounded-lg hover:bg-wave-700 transition-colors">
                    <Plus className="w-4 h-4" /> New Service
                </button>
            </div>

            {/* ── Table ── */}
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            {['Name', 'Slug', 'Mode', 'Endpoints', 'Status', ''].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {services.data.map((s) => (
                            <tr key={s.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{s.name}</td>
                                <td className="px-4 py-3">
                                    <code className="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{s.slug}</code>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={s.mode === 'mock' ? 'badge-mock' : 'badge-proxy'}>{s.mode}</span>
                                </td>
                                <td className="px-4 py-3 text-gray-600">{s.endpoints_count}</td>
                                <td className="px-4 py-3">
                                    <span className={s.is_active ? 'badge-active' : 'badge-inactive'}>
                                        {s.is_active ? 'active' : 'inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2 justify-end">
                                        <button onClick={() => openEdit(s)} className="p-1.5 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100">
                                            <Pencil className="w-3.5 h-3.5" />
                                        </button>
                                        <button onClick={() => destroy(s)} className="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50">
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {services.data.length === 0 && (
                            <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No services yet. Create your first one.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* ── Modal ── */}
            {open && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="font-semibold text-gray-900">{editing ? 'Edit Service' : 'New Service'}</h2>
                            <button onClick={() => setOpen(false)} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={submit} className="px-5 py-4 space-y-4">
                            <Field label="Name" error={errors.name}>
                                <input value={data.name} onChange={e => setData('name', e.target.value)}
                                    className="input-base" placeholder="Bank API" />
                            </Field>
                            <Field label="Slug" error={errors.slug}>
                                <input value={data.slug} onChange={e => setData('slug', e.target.value)}
                                    className="input-base font-mono" placeholder="bank-api" />
                            </Field>
                            <Field label="Base URL" error={errors.base_url}>
                                <input value={data.base_url} onChange={e => setData('base_url', e.target.value)}
                                    className="input-base" placeholder="https://api.example.com" />
                            </Field>
                            <Field label="Description" error={errors.description}>
                                <textarea value={data.description} onChange={e => setData('description', e.target.value)}
                                    className="input-base" rows={2} placeholder="Optional description..." />
                            </Field>
                            <Field label="Default Mode" error={errors.mode}>
                                <select value={data.mode} onChange={e => setData('mode', e.target.value as ServiceMode)} className="input-base">
                                    <option value="mock">Mock</option>
                                    <option value="proxy">Proxy</option>
                                </select>
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

            <style>{`.input-base { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-wave-500 focus:border-transparent; }`}</style>
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

import { ReactNode } from 'react'
