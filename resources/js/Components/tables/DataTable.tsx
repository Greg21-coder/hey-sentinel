import React, { ReactNode } from 'react';
import { router } from '@inertiajs/react';
import Button from '@/Components/ui/Button';
import Input from '@/Components/ui/Input';
import Select from '@/Components/ui/Select';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface Column<T> {
    key: keyof T | string;
    label: string;
    sortable?: boolean;
    render?: (row: T) => ReactNode;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
}

export interface PaginationLinks {
    prev: string | null;
    next: string | null;
}

export interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    [key: string]: unknown;
}

export type FilterType = 'text' | 'select' | 'toggle';

export interface FilterConfig {
    key: string;
    label: string;
    type: FilterType;
    options?: { value: string; label: string }[];
}

interface DataTableProps<T extends { id: number | string }> {
    columns: Column<T>[];
    data?: T[];
    pagination?: LaravelPagination<T>;
    meta?: PaginationMeta;
    links?: PaginationLinks;
    filters?: FilterConfig[];
    currentFilters?: Record<string, string>;
    currentSort?: string;
    currentDirection?: 'asc' | 'desc';
    rowActions?: (row: T) => ReactNode;
    headerCheckbox?: { checked: boolean; onChange: () => void };
    emptyMessage?: string;
}

// ─── Sort icon ────────────────────────────────────────────────────────────────

const SortIcon: React.FC<{ direction?: 'asc' | 'desc' | null }> = ({
    direction,
}) => {
    if (!direction)
        return (
            <svg
                className="ml-1 h-4 w-4 text-gray-300"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    fillRule="evenodd"
                    d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    clipRule="evenodd"
                />
            </svg>
        );
    if (direction === 'asc')
        return (
            <svg
                className="ml-1 h-4 w-4 text-gray-700"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    fillRule="evenodd"
                    d="M10 17a.75.75 0 01-.55-.24l-3.25-3.5a.75.75 0 111.1-1.02L10 15.148l2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5A.75.75 0 0110 17zm-3.76-13.8a.75.75 0 011.06-.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0L6.25 4.18a.75.75 0 01-.01-1.06z"
                    clipRule="evenodd"
                />
            </svg>
        );
    return (
        <svg
            className="ml-1 h-4 w-4 text-gray-700"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                fillRule="evenodd"
                d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                clipRule="evenodd"
            />
        </svg>
    );
};

// ─── DataTable ────────────────────────────────────────────────────────────────

function DataTable<T extends { id: number | string }>({
    columns,
    data: dataProp,
    pagination,
    meta: metaProp,
    links: linksProp,
    filters = [],
    currentFilters = {},
    currentSort,
    currentDirection,
    rowActions,
    headerCheckbox,
    emptyMessage = 'No records found.',
}: DataTableProps<T>) {
    const data = dataProp ?? pagination?.data ?? [];
    const meta: PaginationMeta | undefined = metaProp ?? (pagination ? {
        current_page: pagination.current_page,
        last_page: pagination.last_page,
        from: pagination.from,
        to: pagination.to,
        total: pagination.total,
        per_page: pagination.per_page,
    } : undefined);
    const links: PaginationLinks | undefined = linksProp ?? (pagination ? {
        prev: pagination.prev_page_url,
        next: pagination.next_page_url,
    } : undefined);
    const navigate = (params: Record<string, string | undefined>) => {
        const merged: Record<string, string> = {};
        for (const [k, v] of Object.entries({
            ...currentFilters,
            sort: currentSort,
            direction: currentDirection,
            ...params,
        })) {
            if (v !== undefined && v !== '') {
                merged[k] = v;
            }
        }
        router.get(window.location.pathname, merged, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSort = (key: string) => {
        if (currentSort === key) {
            navigate({
                sort: key,
                direction: currentDirection === 'asc' ? 'desc' : 'asc',
                page: '1',
            });
        } else {
            navigate({ sort: key, direction: 'asc', page: '1' });
        }
    };

    const handleFilterChange = (key: string, value: string) => {
        navigate({ [key]: value, page: '1' });
    };

    const handlePrev = () => {
        if (meta && meta.current_page > 1) {
            navigate({ page: String(meta.current_page - 1) });
        }
    };

    const handleNext = () => {
        if (meta && meta.current_page < meta.last_page) {
            navigate({ page: String(meta.current_page + 1) });
        }
    };

    const hasRowActions = !!rowActions;

    return (
        <div className="space-y-4">
            {/* Filter bar */}
            {filters.length > 0 && (
                <div className="flex flex-wrap gap-3 items-end">
                    {filters.map((filter) => {
                        if (filter.type === 'text') {
                            return (
                                <div key={filter.key} className="w-48">
                                    <Input
                                        label={filter.label}
                                        value={currentFilters[filter.key] ?? ''}
                                        placeholder={`Filter by ${filter.label.toLowerCase()}...`}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                filter.key,
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            );
                        }

                        if (filter.type === 'select' && filter.options) {
                            return (
                                <div key={filter.key} className="w-48">
                                    <Select
                                        label={filter.label}
                                        value={currentFilters[filter.key] ?? ''}
                                        options={filter.options}
                                        placeholder={`All ${filter.label.toLowerCase()}`}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                filter.key,
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            );
                        }

                        if (filter.type === 'toggle') {
                            return (
                                <div key={filter.key} className="flex items-center gap-2 mt-5">
                                    <input
                                        id={`filter-toggle-${filter.key}`}
                                        type="checkbox"
                                        className="h-4 w-4 rounded border-gray-300 text-shopify-500 focus:ring-shopify-500"
                                        checked={
                                            currentFilters[filter.key] === '1'
                                        }
                                        onChange={(e) =>
                                            handleFilterChange(
                                                filter.key,
                                                e.target.checked ? '1' : '',
                                            )
                                        }
                                    />
                                    <label
                                        htmlFor={`filter-toggle-${filter.key}`}
                                        className="text-sm text-gray-700"
                                    >
                                        {filter.label}
                                    </label>
                                </div>
                            );
                        }

                        return null;
                    })}
                </div>
            )}

            {/* Table */}
            <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={String(col.key)}
                                    scope="col"
                                    className={[
                                        'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap',
                                        col.sortable
                                            ? 'cursor-pointer select-none hover:text-gray-700'
                                            : '',
                                    ].join(' ')}
                                    onClick={
                                        col.sortable
                                            ? () => handleSort(String(col.key))
                                            : undefined
                                    }
                                >
                                    {headerCheckbox && String(col.key) === 'select' ? (
                                        <input
                                            type="checkbox"
                                            className="h-4 w-4 rounded border-gray-300 text-shopify-500 focus:ring-shopify-500"
                                            checked={headerCheckbox.checked}
                                            onChange={headerCheckbox.onChange}
                                        />
                                    ) : (
                                        <span className="inline-flex items-center">
                                            {col.label}
                                            {col.sortable && (
                                                <SortIcon
                                                    direction={
                                                        currentSort === String(col.key)
                                                            ? currentDirection ?? null
                                                            : null
                                                    }
                                                />
                                            )}
                                        </span>
                                    )}
                                </th>
                            ))}
                            {hasRowActions && (
                                <th
                                    scope="col"
                                    className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Actions
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={
                                        columns.length + (hasRowActions ? 1 : 0)
                                    }
                                    className="px-4 py-12 text-center text-sm text-gray-500"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row) => (
                                <tr
                                    key={row.id}
                                    className="hover:bg-gray-50 transition-colors duration-150"
                                >
                                    {columns.map((col) => (
                                        <td
                                            key={String(col.key)}
                                            className="px-4 py-3 text-gray-700 whitespace-nowrap"
                                        >
                                            {col.render
                                                ? col.render(row)
                                                : String(
                                                      (row as Record<string, unknown>)[
                                                          String(col.key)
                                                      ] ?? '',
                                                  )}
                                        </td>
                                    ))}
                                    {hasRowActions && (
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            {rowActions!(row)}
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {meta && meta.last_page > 1 && (() => {
                const current = meta.current_page;
                const last = meta.last_page;
                const pages: (number | '...')[] = [];

                if (last <= 7) {
                    for (let i = 1; i <= last; i++) pages.push(i);
                } else {
                    pages.push(1);
                    if (current > 3) pages.push('...');
                    const start = Math.max(2, current - 1);
                    const end = Math.min(last - 1, current + 1);
                    for (let i = start; i <= end; i++) pages.push(i);
                    if (current < last - 2) pages.push('...');
                    pages.push(last);
                }

                return (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-gray-600">
                            {meta.from !== null && meta.to !== null ? (
                                <>
                                    Showing{' '}
                                    <span className="font-medium">{meta.from}</span> to{' '}
                                    <span className="font-medium">{meta.to}</span> of{' '}
                                    <span className="font-medium">{meta.total}</span>{' '}
                                    results
                                </>
                            ) : (
                                `Page ${current} of ${last}`
                            )}
                        </p>
                        <div className="flex items-center gap-1">
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={handlePrev}
                                disabled={current === 1}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                            </Button>
                            {pages.map((p, i) =>
                                p === '...' ? (
                                    <span key={`ellipsis-${i}`} className="px-2 text-sm text-gray-400">...</span>
                                ) : (
                                    <button
                                        key={p}
                                        onClick={() => navigate({ page: String(p) })}
                                        className={[
                                            'min-w-[32px] px-2 py-1 text-sm font-medium rounded-md',
                                            p === current
                                                ? 'bg-shopify-500 text-white'
                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
                                        ].join(' ')}
                                    >
                                        {p}
                                    </button>
                                )
                            )}
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={handleNext}
                                disabled={current === last}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
                            </Button>
                        </div>
                    </div>
                );
            })()}
        </div>
    );
}

export default DataTable;
