import scale from "@/console/commands/scale";
import dates from "@/console/commands/dates";
import {ApiUrl} from "@/constants/api";

export type Setters = {
    setScale: Function,
    setDates: Function,
    setUrl: Function,
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
        case 'display':
            if (!args[1]) {
                lines.unshift(`Subject is not specified.`);
                break;
            }
            switch (args[1]) {
                case 'schedule':
                    if (!args[2]) {
                        lines.unshift(`Date is not specified.`);
                        break;
                    }
                    const date = args[2];
                    setters.setUrl(ApiUrl.SCHEDULE.replace('{date}', date));
                    break;
                case 'tasks':
                    setters.setUrl(ApiUrl.TASKS);
                    break;
                default:
                    lines.unshift(`Subject is unknown.`);
                    break;
            }
            break;
        default:
            lines.unshift(`Command "${args[0]}" is not supported.`);
    }
    return lines.map(line => `< ${line}`);
}
