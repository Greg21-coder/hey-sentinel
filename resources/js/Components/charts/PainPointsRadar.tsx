import React from 'react';
import {
    RadarChart,
    PolarGrid,
    PolarAngleAxis,
    PolarRadiusAxis,
    Radar,
    ResponsiveContainer,
} from 'recharts';

interface Props {
    labels: string[];
    counts: number[];
}

const PainPointsRadar: React.FC<Props> = ({ labels, counts }) => {
    if (!labels.length) {
        return (
            <div className="flex items-center justify-center h-[320px] text-sm text-gray-400">
                No pain point data available yet.
            </div>
        );
    }

    const data = labels.map((label, i) => ({
        subject: label,
        count: counts[i] ?? 0,
    }));

    return (
        <ResponsiveContainer width="100%" height={320}>
            <RadarChart data={data} margin={{ top: 16, right: 24, left: 24, bottom: 16 }}>
                <PolarGrid stroke="#e5e7eb" />
                <PolarAngleAxis
                    dataKey="subject"
                    tick={{ fontSize: 11, fill: '#6b7280' }}
                />
                <PolarRadiusAxis
                    tick={{ fontSize: 10, fill: '#9ca3af' }}
                    axisLine={false}
                />
                <Radar
                    name="Mentions"
                    dataKey="count"
                    stroke="#6366f1"
                    fill="#6366f1"
                    fillOpacity={0.25}
                    strokeWidth={2}
                />
            </RadarChart>
        </ResponsiveContainer>
    );
};

export default PainPointsRadar;
