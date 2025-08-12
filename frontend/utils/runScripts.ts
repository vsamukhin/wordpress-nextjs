export function runScriptsFromHTML(html: string) {
  if (!html) return;

  const parser = new DOMParser();
  const doc = parser.parseFromString(html, 'text/html');

  doc.querySelectorAll('script').forEach((oldScript) => {
    const src = oldScript.src;

    if (src && document.querySelector(`script[src="${src}"]`)) {
      return;
    }

    if (!src && oldScript.textContent) {
      const code = oldScript.textContent.trim();
      if ([...document.scripts].some(s => s.textContent?.trim() === code)) {
        return;
      }
    }

    const script = document.createElement('script');
    if (src) {
      script.src = src;
      script.async = false;
    } else if (oldScript.textContent?.trim()) {
      script.textContent = oldScript.textContent;
    }
    document.body.appendChild(script);
  });
}
