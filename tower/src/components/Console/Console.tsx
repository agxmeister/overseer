import styles from './Console.module.sass'
import React, {useState} from "react";

export default function Console()
{
    const [lines, setLines] = useState<string[]>(['> ']);
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
                    break;
                case 'Backspace':
                    if (lines[0].length > 2) {
                        lines[0] = lines[0].slice(0, -1);
                    }
                    break;
                default:
                    break;
            }
        }
        setLines([...lines]);
    }

    const run = (command: string) => {
        const args = command.split(' ');
        switch (args[0]) {
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
