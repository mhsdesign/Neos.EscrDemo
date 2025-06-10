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
import {useState} from "react";
import {handleCommand} from "./Api/HandleCommand";

export const App = () => {
    const commandsFetch = useAsync(availableCommands);
    const [selectedCommand, selectCommand] = useState<string|null>(null);
    const [selectedOptions, selectOptions] = useState<Record<string,string>>({});

    const [issueCommandState, issueCommand] = useAsyncFn(() => {
        return handleCommand(selectedCommand, selectedOptions);
    }, [selectedCommand, selectedOptions])

    if (commandsFetch.loading) {
        return "";
    }

    return <Grid
        first={
            <>
                <Headline title="Neos Escr Demo" />
                <Input options={['', ...Object.keys(commandsFetch.value)]} value={selectedCommand ?? ''} onChange={selectCommand} />
                {selectedCommand
                    ? <>
                        <InputValueList>
                            {commandsFetch.value[selectedCommand].map((argumentName) => <InputValue name={argumentName} value={selectedOptions[argumentName] ?? ''} onChange={(argumentValue) => { selectOptions({...selectedOptions, [argumentName]: argumentValue}) }} />)}
                        </InputValueList>
                        <Button label="Issue command" onClick={issueCommand} />
                        {issueCommandState.loading
                            ? "Loading..."
                            : issueCommandState.error
                                ? `Last command failed: ${issueCommandState.error.message}`
                                : issueCommandState.value !== undefined ? "Command succeeded" : ""
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
                <Select label="Workspace" value="Live" onChange={() => {}} />
                <Select label="Dimension Space Point" value="{}" onChange={() => {}} />
                <Select label="Visibility Constraints" value="[]" onChange={() => {}} />
            </SelectList>
            <Preview text={"test"} />
        </>}
    />
}
