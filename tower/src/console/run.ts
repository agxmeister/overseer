import scale from "@/console/commands/scale";
import dates from "@/console/commands/dates";
import schedule from "@/console/commands/schedule";

export type Setters = {
    setScale: Function,
    setDates: Function,
    setSchedule: Function,
}

export default function run(command: string, setters: Setters): string[]
{
    const lines = [];
    const args = command.split(' ');
    switch (args[0]) {
        case 'scale':
            lines.unshift(...scale(args, setters.setScale));
            break;
        case 'dates':
            lines.unshift(...dates(args, setters.setDates));
            break;
        case 'schedule':
            lines.unshift(...schedule(args, setters.setSchedule));
            break;
        default:
            lines.unshift(`Command "${args[0]}" is not supported.`);
    }
    return lines.map(line => `< ${line}`);
}
