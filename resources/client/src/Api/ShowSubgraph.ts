
export type DimensionSpacePoint = Record<string, string>;
export type VisibilityConstraints = string[];

export const showSubgraph = (workspaceName: string, dimensionSpacePoint: DimensionSpacePoint, visibilityConstraints: VisibilityConstraints): Promise<string> =>
    fetch(`/api/subgraph/${workspaceName}/${encodeURIComponent(JSON.stringify(dimensionSpacePoint))}/${encodeURIComponent(JSON.stringify(visibilityConstraints))}`)
        .then(r => r.json())
        .then(json => {
            if ("error" in json) {
                throw Error(json.error.message)
            }
            if ("success" in json) {
                return json.success;
            }
            throw Error('Unexpected json response.')
        });
