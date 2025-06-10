import * as React from "react";
import styles from "./Select.module.css";

export const Select = (props: { label: string, value: string, onChange: (value: string) => void }) => {
    return <div className={styles.select}>
        <div>{props.label}</div>
        <input value={props.value} onChange={(e) => props.onChange(e.target.value)}/>
    </div>
}
