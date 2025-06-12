
export const getNodeTypes = (): Promise<string> => fetch('/api/nodeTypes').then(r => r.text() as Promise<string>);
