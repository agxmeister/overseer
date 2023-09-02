import scale from "@/console/commands/scale";
import dates from "@/console/commands/dates";
import schedule from "@/console/commands/schedule";
import task from "@/console/commands/task";
import {Issue} from "@/types/Issue";

export type Context = {
    issues: Issue[],
    schedule: Issue[],
}

export type Setters = {
    setScale: Function,
    setDates: Function,
    setSchedule: Function,
    onMutate: Function,
}

export default async function run(command: string, context: Context, setters: Setters): Promise<string[]>
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
            lines.unshift(...schedule(args, context.issues, context.schedule, setters.setSchedule, setters.onMutate));
            break;
        case 'task':
            lines.unshift(...task(args, setters.onMutate));
            break;
        default:
            lines.unshift(`Command "${args[0]}" is not supported.`);
    }
    return lines.map(line => `< ${line}`);
}
