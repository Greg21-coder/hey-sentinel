import { ChangeEvent } from 'react';

export interface AppOption {
  id: number;
  name: string;
}

interface Props {
  options: AppOption[];
  selectedId: number;
  onChange: (id: number) => void;
}

export default function MineAppSelector({ options, selectedId, onChange }: Props) {
  return (
    <select
      className="rounded border-gray-300 text-sm"
      value={selectedId}
      onChange={(e: ChangeEvent<HTMLSelectElement>) => onChange(Number(e.target.value))}
    >
      {options.map((o) => (
        <option key={o.id} value={o.id}>{o.name}</option>
      ))}
    </select>
  );
}
