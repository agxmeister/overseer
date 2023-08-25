import styles from './Console.module.sass'
import React, {useState} from "react";
import {ApiUrl} from "@/constants/api";

export type ConsoleProps = {
    setScale: Function;
    setUrl: Function;
}

export default function Console({setScale, setUrl}: ConsoleProps)
{
    const [lines, setLines] = useState<string[]>(['> ']);
    const [index, setIndex] = useState<number>(0);
    const inputLines = lines
        .filter((value: string, index: number) => index > 0 && value.startsWith('> '))
        .filter((value: string, index: number, array: string[]) => array.indexOf(value) === index);
    inputLines.unshift('> ');
    let inputIndex = index;
    const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (event.key.length === 1) {
            lines[0] = lines[0] + event.key;
        } else {
            switch (event.key) {
                case 'Enter':
                    if (lines[0] === '> ') {
                        break;
                    }
                    run(lines[0].slice(2));
                    lines.unshift('> ');
                    inputIndex = 0;
                    break;
                case 'Backspace':
                    if (lines[0].length > 2) {
                        lines[0] = lines[0].slice(0, -1);
                    }
                    break;
                case 'ArrowUp':
                    if (inputIndex < inputLines.length - 1) {
                        inputIndex++;
                    }
                    lines[0] = inputLines[inputIndex];
                    break;
                case 'ArrowDown':
                    if (inputIndex > 0) {
                        inputIndex--;
                    }
                    lines[0] = inputLines[inputIndex];
                    break;
                default:
                    break;
            }
        }
        event.preventDefault();
        setLines([...lines]);
        setIndex(inputIndex);
    }

    const run = (command: string) => {
        const args = command.split(' ');
        switch (args[0]) {
            case 'set-scale':
                if (!args[1]) {
                    lines.unshift(`< Scale is not specified.`);
                    break;
                }
                const scale = parseFloat(args[1]);
                if (isNaN(scale)) {
                    lines.unshift(`< Scale must be a number.`);
                    break;
                }
                if (scale <= 0) {
                    lines.unshift(`< Scale must be grater than 0.`);
                    break;
                }
                setScale(scale);
                break;
            case 'display':
                if (!args[1]) {
                    lines.unshift(`< Subject is not specified.`);
                    break;
                }
                switch (args[1]) {
                    case 'schedule':
                        if (!args[2]) {
                            lines.unshift(`< Date is not specified.`);
                            break;
                        }
                        const date = args[2];
                        setUrl(ApiUrl.SCHEDULE.replace('{date}', date));
                        break;
                    case 'tasks':
                        setUrl(ApiUrl.TASKS);
                        break;
                    default:
                        lines.unshift(`< Subject is unknown.`);
                        break;
                }
                break;
            default:
                lines.unshift(`< Command "${args[0]}" is not supported.`);
        }
    }

    return (
        <div tabIndex={1} className={styles.console} onKeyDown={handleKeyDown}>
            {lines.map((line, index) =>
                <div key={`line-${index}`}>
                    {index > 0 && line.startsWith('< ') ?
                        <span className={styles.comment}>{line.slice(2)}</span> :
                        null
                    }
                    {index > 0 && line.startsWith('> ') ?
                        <span className={styles.command}>{line.slice(2)}</span> :
                        null
                    }
                    {index === 0 ?
                        <span className={styles.command}>{line}</span> :
                        null
                    }
                    {index === 0 ? <span className={styles.cursor}>&nbsp;</span> : null}
                </div>
            )}
        </div>
    );
}
