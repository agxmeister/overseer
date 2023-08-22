import styles from './Console.module.sass'
import React from "react";

export type ConsoleProps = {
    lines: string[];
}

export default function Console({lines}: ConsoleProps)
{
    return (
        <div className={styles.console}>
            {lines.map((line, index) =>
                <div key={`line-${index}`}>
                    <span>{line}</span>
                    {index === 0 ? <span className={styles.cursor}>&nbsp;</span> : null}
                </div>
            )}
        </div>
    );
}
