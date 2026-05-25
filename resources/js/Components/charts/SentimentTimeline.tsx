import React from 'react';
import {
    LineChart,
    Line,
    CartesianGrid,
    XAxis,
    YAxis,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';

interface Props {
    labels: string[];
    positive: number[];
    neutral: number[];
    mixed: number[];
    negative: number[];
}

const SentimentTimeline: React.FC<Props> = ({
    labels,
    positive,
    neutral,
    mixed,
    negative,
}) => {
    const data = labels.map((label, i) => ({
        label,
        Positive: positive[i] ?? 0,
        Neutral: neutral[i] ?? 0,
        Mixed: mixed[i] ?? 0,
        Negative: negative[i] ?? 0,
    }));

    return (
        <ResponsiveContainer width="100%" height={320}>
            <LineChart data={data} margin={{ top: 8, right: 16, left: 0, bottom: 8 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                <XAxis
                    dataKey="label"
                    tick={{ fontSize: 12, fill: '#6b7280' }}
                    axisLine={{ stroke: '#e5e7eb' }}
                    tickLine={false}
                />
                <YAxis
                    tick={{ fontSize: 12, fill: '#6b7280' }}
                    axisLine={false}
                    tickLine={false}
                />
                <Tooltip
                    contentStyle={{
                        borderRadius: '8px',
                        border: '1px solid #e5e7eb',
                        fontSize: '12px',
                    }}
                />
                <Legend wrapperStyle={{ fontSize: '12px' }} />
                <Line
                    type="monotone"
                    dataKey="Positive"
                    stroke="#008060"
                    strokeWidth={2}
                    dot={false}
                />
                <Line
                    type="monotone"
                    dataKey="Neutral"
                    stroke="#64748b"
                    strokeWidth={2}
                    dot={false}
                />
                <Line
                    type="monotone"
                    dataKey="Mixed"
                    stroke="#ffc453"
                    strokeWidth={2}
                    dot={false}
                />
                <Line
                    type="monotone"
                    dataKey="Negative"
                    stroke="#d72c0d"
                    strokeWidth={2}
                    dot={false}
                />
            </LineChart>
        </ResponsiveContainer>
    );
};

export default SentimentTimeline;
