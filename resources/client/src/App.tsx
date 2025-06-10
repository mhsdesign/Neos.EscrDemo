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

export const App = () => {
    return <Grid
        first={
            <>
                <Headline title="Neos Escr Demo" />
                <Input options={["CreateNode", "Lol"]} />
                <InputValueList>
                    <InputValue name="Workspace name" value="" onChange={() => {}} />
                    <InputValue name="Node id" value="" onChange={() => {}} />
                </InputValueList>
                <Button label="Issue command" onClick={() => {}} />
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
