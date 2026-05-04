---
name: inertia-react-development
description: "Develops Inertia.js v3 React client-side applications. Activates when creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, polling, optimistic updates; or when user mentions React with Inertia, React pages, React forms, or React navigation."
license: MIT
metadata:
  author: laravel
---

# Inertia React Development (v3)

## When to Apply

Activate this skill when:

- Creating or modifying React page components for Inertia
- Working with forms in React (using `<Form>` or `useForm`)
- Implementing client-side navigation with `<Link>` or `router`
- Using v3 features: deferred props, prefetching, WhenVisible, InfiniteScroll, once props, flash data, polling, optimistic updates
- Building React-specific features with the Inertia protocol

## Documentation

Use `search-docs` for detailed Inertia v3 React patterns and documentation.

## Project Setup (v3)

This project uses **Inertia.js v3** with:
- `@inertiajs/react@^3.0` + `@inertiajs/vite@^3.0`
- The `@inertiajs/vite` plugin in `vite.config.ts` handles automatic page resolution — **no `resolve` callback needed in `createInertiaApp`**
- React 19 required (already satisfied)
- Axios is **not used by Inertia v3** internally (it has its own XHR client), but is still used in the project for direct API calls (`/api/admin/*`)

### Key v3 Changes from v2

- `future` config namespace removed — all v2 `future` options are now defaults
- `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers` is no longer needed
- New: optimistic updates with automatic rollback
- New: layout props for data sharing
- New: standalone HTTP requests
- New: SSR out-of-the-box (no separate Node.js server)

## Basic Usage

### Page Components Location

React page components should be placed in the `resources/js/Pages` directory. Use `@/types` for all TypeScript types.

### Page Component Structure

```tsx
import AppLayout from '@/Components/layout/AppLayout'
import type { Service } from '@/types'

interface Props {
    services: Service[]
}

export default function ServicesIndex({ services }: Props) {
    return (
        <AppLayout>
            <ul>
                {services.map(service => (
                    <li key={service.id}>{service.name}</li>
                ))}
            </ul>
        </AppLayout>
    )
}
```

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

```tsx
import { Link, router } from '@inertiajs/react'

<Link href="/">Home</Link>
<Link href="/services">Services</Link>
<Link href={`/services/${service.id}`}>View Service</Link>
```

### Link with Method

```tsx
import { Link } from '@inertiajs/react'

<Link href="/logout" method="post" as="button">
    Logout
</Link>
```

### Prefetching

```tsx
import { Link } from '@inertiajs/react'

<Link href="/services" prefetch>
    Services
</Link>
```

### Programmatic Navigation

```tsx
import { router } from '@inertiajs/react'

router.visit('/services')

router.visit('/services', {
    method: 'post',
    data: { name: 'My Service' },
    onSuccess: () => console.log('Done'),
})
```

## Form Handling

### Form Component (Recommended for simple forms)

```tsx
import { Form } from '@inertiajs/react'

export default function CreateService() {
    return (
        <Form action="/services" method="post">
            {({ errors, processing, wasSuccessful }) => (
                <>
                    <input type="text" name="name" />
                    {errors.name && <div>{errors.name}</div>}

                    <button type="submit" disabled={processing}>
                        {processing ? 'Creating...' : 'Create'}
                    </button>

                    {wasSuccessful && <div>Created!</div>}
                </>
            )}
        </Form>
    )
}
```

### `useForm` Hook (Recommended for programmatic control)

```tsx
import { useForm } from '@inertiajs/react'

export default function CreateService() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        base_url: '',
        mode: 'mock' as const,
    })

    function submit(e: React.FormEvent) {
        e.preventDefault()
        post('/api/admin/services', {
            onSuccess: () => reset(),
        })
    }

    return (
        <form onSubmit={submit}>
            <input
                type="text"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
            />
            {errors.name && <div>{errors.name}</div>}

            <button type="submit" disabled={processing}>
                {processing ? 'Saving...' : 'Save'}
            </button>
        </form>
    )
}
```

## Inertia v3 Features

### Deferred Props

Use deferred props to load data after initial page render:

```tsx
export default function LogsIndex({ logs }: { logs?: Log[] }) {
    return (
        <div>
            {!logs ? (
                <div className="animate-pulse">
                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
            ) : (
                <ul>
                    {logs.map(log => (
                        <li key={log.id}>{log.path}</li>
                    ))}
                </ul>
            )}
        </div>
    )
}
```

### Polling

```tsx
import { usePoll } from '@inertiajs/react'

export default function Dashboard({ stats }) {
    usePoll(5000)

    return <div>Active Users: {stats.activeUsers}</div>
}
```

### WhenVisible

```tsx
import { WhenVisible } from '@inertiajs/react'

<WhenVisible data="stats" fallback={<div className="animate-pulse">Loading...</div>}>
    {() => <div>Stats loaded</div>}
</WhenVisible>
```

### InfiniteScroll

```tsx
import { InfiniteScroll } from '@inertiajs/react'

export default function Logs({ logs }) {
    return (
        <InfiniteScroll data="logs">
            {logs.data.map(log => (
                <div key={log.id}>{log.path}</div>
            ))}
        </InfiniteScroll>
    )
}
```

## Common Pitfalls

- Using traditional `<a>` links instead of `<Link>` (breaks SPA behavior)
- Forgetting loading states (skeleton screens) when using deferred props
- Not handling the `undefined` state of deferred props before data loads
- Using `<form>` without preventing default submission (use `<Form>` component or `e.preventDefault()`)
- Adding `resolve` callback to `createInertiaApp` — not needed in v3, the `@inertiajs/vite` plugin handles it
- Using `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers` — remove in v3
- Expecting `future` config to work — removed in v3, all features are now always on
