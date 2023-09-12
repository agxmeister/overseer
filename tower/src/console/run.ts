import scale from "@/console/commands/scale";
import dates from "@/console/commands/dates";
import schedule from "@/console/commands/schedule";
import task from "@/console/commands/task";
import {Issue} from "@/types/Issue";
import {Schedule} from "@/types/Schedule";

export type Context = {
    issues: Issue[],
    schedule: Schedule[],
}

export type Setters = {
    setScale: Function,
    setDates: Function,
    setMode: Function,
    setSchedule: Function,
    onTaskResize: Function,
    onLink: Function,
    onUnlink: Function,
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
            lines.unshift(...await schedule(args, context.issues, context.schedule, setters.setMode, setters.setSchedule, setters.onTaskResize, setters.onLink, setters.onUnlink));
            break;
        case 'task':
            lines.unshift(...await task(args, setters.onTaskResize, setters.onLink, setters.onUnlink));
            break;
        default:
            lines.unshift(`Command "${args[0]}" is not supported.`);
    }
    return lines.map(line => `< ${line}`);
}
