import * as React from "react";
import styles from "./Headline.module.css";

export const Headline = (props: { title: string }) => {
    return <h1 className={styles.headline}>
        {props.title}
    </h1>
}
