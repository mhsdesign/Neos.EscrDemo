
export type Commands = Record<string, string[]>

export const availableCommands = (): Promise<Commands> => fetch('/api/commands').then(r => r.json() as Promise<Commands>);
