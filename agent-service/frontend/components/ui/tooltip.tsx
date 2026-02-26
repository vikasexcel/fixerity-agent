'use client';

import * as React from 'react';

const TooltipProviderContext = React.createContext<undefined>(undefined);

export function TooltipProvider({
  children,
  ...props
}: React.HTMLAttributes<HTMLDivElement> & { children: React.ReactNode }) {
  return (
    <TooltipProviderContext.Provider value={undefined}>
      <div {...props}>{children}</div>
    </TooltipProviderContext.Provider>
  );
}
