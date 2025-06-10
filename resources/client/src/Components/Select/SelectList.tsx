import * as React from "react";
import styles from "./Select.module.css";

export const SelectList = (props: { children: React.ReactElement[] }) => {
    return <div className={styles.selectList}>
        {props.children}
    </div>
}
