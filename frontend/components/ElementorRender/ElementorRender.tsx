/* eslint-disable @typescript-eslint/no-explicit-any */
'use client';
import { runScriptsFromHTML } from '@/utils/runScripts';
import { useEffect, useRef } from 'react';

interface Props {
  body: string;
  footer?: string;
  bodyClass: string;
}

export default function ElementorRender({ body, footer, bodyClass }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (bodyClass) {
      document.body.classList.add(bodyClass);
    }

    if (body) runScriptsFromHTML(body);
    if (footer) runScriptsFromHTML(footer);

    setTimeout(() => {
      if ((window as any).jQuery && (window as any).elementorFrontend?.init) {
        (window as any).elementorFrontend.init();
      }
    }, 300);

  }, [body, footer, bodyClass]);

  return (
    <div
      ref={containerRef}
      dangerouslySetInnerHTML={{ __html: body }}
      suppressHydrationWarning
    />
  );
}
