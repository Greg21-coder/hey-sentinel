interface MatrixRow {
  name: string;
  presence: boolean[];
}

interface Props {
  matrix: MatrixRow[];
  columnLabels: string[];
  pendingFlags: boolean[];
}

export default function FeatureMatrix({ matrix, columnLabels, pendingFlags }: Props) {
  if (matrix.length === 0) {
    return <p className="text-sm text-gray-500">Sin features extraídas — la app necesita más reseñas (mínimo 3).</p>;
  }

  return (
    <table className="w-full text-sm">
      <thead>
        <tr>
          <th className="text-left text-xs uppercase text-gray-500 pb-1">Feature</th>
          {columnLabels.map((label, i) => (
            <th key={i} className="text-center text-xs uppercase text-gray-500 pb-1">
              {label}
              {pendingFlags[i] && <span className="block text-yellow-700">extracting...</span>}
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {matrix.map((row) => (
          <tr key={row.name} className="border-t">
            <td className="py-1">{row.name}</td>
            {row.presence.map((p, i) => (
              <td key={i} className="text-center py-1">{p ? '✓' : '—'}</td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}
