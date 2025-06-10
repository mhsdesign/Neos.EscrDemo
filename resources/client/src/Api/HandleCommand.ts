
export const handleCommand = (commandName: string, payload: Record<string, string>): Promise<void> =>
    fetch(`/api/command/${commandName}?payload=${encodeURIComponent(JSON.stringify(payload))}`, { method: 'POST' })
        .then(r => r.json())
        .then(json => {
            if ("error" in json) {
                throw Error(json.error.message)
            }
        });
