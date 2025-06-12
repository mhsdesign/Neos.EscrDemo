
export const setNodeTypes = (nodeTypes: string) => fetch(`/api/nodeTypes`, { method: 'PUT', body: nodeTypes, headers: {'Content-Type': 'text/plain; charset=utf-8'} });
