import * as React from "react";
import {Headline} from "./Components/Headline/Headline";
import {Grid} from "./Components/Grid/Grid";
import {Preview} from "./Components/Preview/Preview";
import {SelectList} from "./Components/Select/SelectList";
import {Select} from "./Components/Select/Select";
import {Input} from "./Components/Input/Input";
import {InputValueList} from "./Components/Input/InputValueList";
import {InputValue} from "./Components/Input/InputValue";
import {Button} from "./Components/Input/Button";
import {useAsync, useAsyncFn} from "react-use";
import {availableCommands} from "./Api/AvailableCommands";
import {useEffect, useState} from "react";
import {handleCommand} from "./Api/HandleCommand";
import {showSubgraph} from "./Api/ShowSubgraph";

export const App = () => {
    const commandsState = useAsync(availableCommands);

    const [selectedCommand, selectCommand] = useState<string|null>(null);
    const [selectedOptions, selectOptions] = useState<Record<string,string>>({});

    const [selectedWorkspace, selectWorkspace] = useState<string>('live');
    const [selectedDimensionSpacePoint, selectDimensionSpacePoint] = useState('{}');
    const [selectedVisibilityConstraints, selectVisibilityConstraints] = useState('');

    const [showSubgraphState, invokeShowSubgraph] = useAsyncFn(async () => {
        const dimensionSpacePoint = JSON.parse(selectedDimensionSpacePoint);
        return showSubgraph(selectedWorkspace, dimensionSpacePoint, selectedVisibilityConstraints.split(',').map(str => str.trim()).filter(Boolean));
    }, [selectedWorkspace, selectedDimensionSpacePoint, selectedVisibilityConstraints]);

    const [handleCommandState, invokeHandleCommand] = useAsyncFn(() => {
        return handleCommand(selectedCommand, selectedOptions);
    }, [selectedCommand, selectedOptions]);

    useEffect(() => {
        if (commandsState.loading || handleCommandState.loading) {
            return;
        }
        invokeShowSubgraph();
    }, [selectedWorkspace, selectedDimensionSpacePoint, selectedVisibilityConstraints, handleCommandState])

    if (commandsState.loading) {
        return "";
    }

    return <Grid
        first={
            <>
                <Headline title="Neos Escr Demo" />
                <Input options={['', ...Object.keys(commandsState.value)]} value={selectedCommand ?? ''} onChange={selectCommand} />
                {selectedCommand
                    ? <>
                        <InputValueList>
                            {commandsState.value[selectedCommand].map((argumentName) => <InputValue name={argumentName} value={selectedOptions[argumentName] ?? ''} onChange={(argumentValue) => { selectOptions({...selectedOptions, [argumentName]: argumentValue}) }} />)}
                        </InputValueList>
                        <Button label="Handle command" onClick={invokeHandleCommand} />
                        {handleCommandState.loading
                            ? "Loading..."
                            : handleCommandState.error
                                ? `Last command failed: ${handleCommandState.error.message}`
                                : handleCommandState.value !== undefined ? "Command succeeded" : ""
                        }
                    </>
                    : ""
                }
            </>
        }
        second={<Preview text={`"My.Custom:Root":
    superTypes:
        "Neos.ContentRepository:Root": true

"My.Custom:Node":
    properties:
        title:
            type: string
`} />}
        third={<>
            <SelectList>
                <Select label="Workspace" value={selectedWorkspace} onChange={selectWorkspace} />
                <Select label="Dimension Space Point" value={selectedDimensionSpacePoint} onChange={selectDimensionSpacePoint} />
                <Select label="Visibility Constraints" value={selectedVisibilityConstraints} onChange={selectVisibilityConstraints} />
            </SelectList>
            <Preview
                text={showSubgraphState.loading
                    ? ""
                    : showSubgraphState.error
                        ? `Subgraph failed: ${showSubgraphState.error.message}`
                        : showSubgraphState.value
                }
            />
        </>}
    />
}
