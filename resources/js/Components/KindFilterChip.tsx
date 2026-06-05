import { router } from '@inertiajs/react';

type Kind = 'all' | 'mine' | 'competitor';

interface Props {
  value: Kind;
  partialKey: string;
}

const labels: Record<Kind, string> = {
  all: 'Todos',
  mine: 'Mías',
  competitor: 'Competidores',
};

export default function KindFilterChip({ value, partialKey }: Props) {
  const select = (next: Kind) => {
    if (next === value) return;
    router.get(
      window.location.pathname,
      { kind: next },
      { only: [partialKey, 'kind'], preserveScroll: true, preserveState: true }
    );
  };

  return (
    <div className="inline-flex rounded-md border border-gray-200 bg-white p-0.5 text-sm">
      {(Object.keys(labels) as Kind[]).map((k) => (
        <button
          key={k}
          type="button"
          onClick={() => select(k)}
          className={
            'px-3 py-1 rounded-md ' +
            (value === k
              ? 'bg-primary text-white font-medium'
              : 'text-gray-700 hover:bg-gray-50')
          }
        >
          {labels[k]}
        </button>
      ))}
    </div>
  );
}
