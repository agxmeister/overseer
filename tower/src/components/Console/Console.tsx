import styles from './Console.module.sass'
import React, {useState} from "react";
import run, {Context, Setters} from "@/console/run";

export type ConsoleProps = {
    context: Context;
    setters: Setters;
}

export default function Console({context, setters}: ConsoleProps)
{
    const [lines, setLines] = useState<string[]>(['> ']);
    const [index, setIndex] = useState<number>(0);
    const [position, setPosition] = useState<number>(0);
    const inputLines = lines
        .filter((value: string, index: number) => index > 0 && value.startsWith('> '))
        .filter((value: string, index: number, array: string[]) => array.indexOf(value) === index);
    inputLines.unshift('> ');
    let inputIndex = index;
    let inputPosition = position;
    const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        handleInput(event.key).then(() => {
            setLines([...lines]);
            setIndex(inputIndex);
            setPosition(inputPosition);
        })
        event.preventDefault();
    }

    const handleInput = async (key: string) => {
        if (key.length === 1) {
            lines[0] = lines[0].slice(0, inputPosition + 2) + key + lines[0].slice(inputPosition + 2);
            inputPosition++;
        } else {
            switch (key) {
                case 'Enter':
                    if (lines[0] === '> ') {
                        break;
                    }
                    lines.unshift(...await run(lines[0].slice(2), context, setters));
                    lines.unshift('> ');
                    inputIndex = 0;
                    inputPosition = 0;
                    break;
                case 'Backspace':
                    if (lines[0].length > 2) {
                        lines[0] = lines[0].slice(0, inputPosition + 1) + lines[0].slice(inputPosition + 2);
                        inputPosition--;
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
                case 'ArrowLeft':
                    if (inputPosition > 0) {
                        inputPosition--;
                    }
                    break;
                case 'ArrowRight':
                    if (inputPosition < lines[0].length - 2) {
                        inputPosition++;
                    }
                    break;
                default:
                    break;
            }
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
                        <>
                            <span className={styles.command}>{line.slice(0, position + 2)}</span>
                            <span className={styles.cursor}>{position + 2 < line.length ? line.slice(position + 2, position + 3) : <>&nbsp;</>}</span>
                            <span className={styles.command}>{line.slice(position + 3)}</span>
                        </> :
                        null
                    }
                </div>
            )}
        </div>
    );
}
