import React from 'react'

interface Column<T> {
  key: string
  header: string
  render?: (item: T) => React.ReactNode
  align?: 'left' | 'center' | 'right'
}

interface TableProps<T> {
  columns: Column<T>[]
  data: T[]
  keyExtractor: (item: T) => string | number
  emptyMessage?: string
  loading?: boolean
}

const alignClass = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
} as const

export function Table<T>({ columns, data, keyExtractor, emptyMessage = 'Aucune donnée', loading = false }: TableProps<T>) {
  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full border-collapse">
        <thead>
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                className={`px-3.5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[0.6px] text-text3 font-mono bg-surface2 border-b border-border first:rounded-tl-md last:rounded-tr-md ${alignClass[col.align ?? 'left']}`}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading ? (
            <tr>
              <td colSpan={columns.length} className="text-center py-10 text-text3">
                <div className="flex items-center justify-center gap-2">
                  <div className="w-4 h-4 border-2 border-accent-light border-t-accent rounded-full animate-spin" />
                  Chargement...
                </div>
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td colSpan={columns.length} className="text-center py-10 text-text3 text-[13px]">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((item) => (
              <tr key={keyExtractor(item)} className="hover:bg-surface2 transition-colors">
                {columns.map((col) => (
                  <td
                    key={col.key}
                    className={`px-3.5 py-2.5 text-[13px] text-text2 border-b border-border last-of-type:border-b-0 ${alignClass[col.align ?? 'left']}`}
                  >
                    {col.render ? col.render(item) : String((item as Record<string, unknown>)[col.key] ?? '')}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
