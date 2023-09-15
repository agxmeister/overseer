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
    setScale: (scale: number) => void,
    setDates: (dates: string[]) => void,
    setMode: (mode: string) => void,
    setSchedule: (schedule: Schedule[]) => void,
    onTaskResize: (mutation: {taskId: string, begin?: string, end?: string}) => Promise<void>,
    onLink: (from: string, to: string) => Promise<void>,
    onUnlink: (linkId: number) => Promise<void>,
}

export default async function run(command: string, context: Context, setters: Setters): Promise<string[]>
{
    const lines = [];
    const args = command.split(' ');
    switch (args[0]) {
        case 'scale':
            lines.unshift(...await scale(args, setters));
            break;
        case 'dates':
            lines.unshift(...await dates(args, setters));
            break;
        case 'schedule':
            lines.unshift(...await schedule(args, context, setters));
            break;
        case 'task':
            lines.unshift(...await task(args, context, setters));
            break;
        default:
            lines.unshift(`Command "${args[0]}" is not supported.`);
    }
    return lines.map(line => `< ${line}`);
}
