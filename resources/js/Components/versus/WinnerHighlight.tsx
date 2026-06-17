import { PropsWithChildren } from 'react';

interface Props {
  isWinner: boolean;
}

export default function WinnerHighlight({ isWinner, children }: PropsWithChildren<Props>) {
  if (!isWinner) return <div>{children}</div>;

  return (
    <div className="ring-2 ring-primary rounded-lg p-2 relative">
      <span className="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" aria-label="Winner">
        ★
      </span>
      {children}
    </div>
  );
}
