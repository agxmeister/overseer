import task from "@/console/commands/task";
import {Mode} from "@/types/Schedule";
import {getActionArg} from "@/console/utils";
import {Context, Setters} from "@/console/run";
import {Link, Type as LinkType} from "@/types/Link";
import {getSchedule} from "@/api/schedule";
import {Issue} from "@/types/Issue";

enum Action {
    Create = "create",
    Reset = "reset",
    Apply = "apply",
    Rollback = "rollback",
    Mode = "mode",
}

export default async function schedule(args: string[], context: Context, setters: Setters): Promise<string[]>
{
    const lines: string[] = [];
    try {
        const action = getActionArg(args, Object.values<string>(Action));
        switch (action) {
            case Action.Create:
                await getSchedule(getDateArg(args))
                    .then(data => {
                        setters.setSchedule(data);
                        setters.setMode(Mode.Edit);
                    });
                break;
            case Action.Reset:
                await unlink(context, setters, lines);
                setters.setSchedule({issues: [], criticalChain: [], buffers: [], links: []});
                setters.setMode(Mode.View);
                break;
            case Action.Apply:
                await unlink(context, setters, lines);

                const promises: Promise<string[]>[] = [];

                const issueKeys = context.issues.map((issue: Issue) => issue.key);
                context.links
                    .filter((link: Link) => link.type === LinkType.Schedule)
                    .filter((link: Link) => issueKeys.includes(link.from) && issueKeys.includes(link.to))
                    .forEach(link => {
                        const promise = task([
                            'task',
                            'link',
                            `from=${link.from}`,
                            `to=${link.to}`
                        ], context, setters);
                        promises.push(promise);
                        promise.then(output => lines.unshift(...output));
                    });

                for (const issue of context.issues) {
                    const promise = task([
                        'task',
                        'resize',
                        issue.key,
                        `begin=${issue.begin}`,
                        `end=${issue.end}`
                    ], context, setters);
                    promises.push(promise);
                    promise.then(output => lines.unshift(...output));
                }

                await Promise.all(promises);
                
                setters.setSchedule({issues: [], criticalChain: [], buffers: [], links: []});
                setters.setMode(Mode.View);
                break;
            case Action.Rollback:
                setters.setSchedule({issues: [], criticalChain: [], buffers: [], links: []});
                setters.setMode(Mode.View);
                break;
            case Action.Mode:
                const mode = getModeArg(args);
                setters.setMode(mode);
                break;
        }
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

async function unlink(context: Context, setters: Setters, lines: string[])
{
    const promises: Promise<string[]>[] = [];

    const issueKeys = context.issues.map((issue: Issue) => issue.key);
    context.links
        .filter((link: Link) => link.type === LinkType.Schedule)
        .filter((link: Link) => issueKeys.includes(link.from) && issueKeys.includes(link.to))
        .forEach(link => {
            const promise = task([
                'task',
                'unlink',
                `from=${link.from}`,
                `to=${link.to}`,
                `type=${LinkType.Schedule}`,
            ], context, setters);
            promises.push(promise);
            promise.then(output => lines.unshift(...output));
        });

    await Promise.all(promises);
}

function getModeArg(args: string[]): string
{
    if (!args[2]) {
        throw `Mode is not specified.`;
    }
    if (!Object.values<string>(Mode).includes(args[2])) {
        throw `Mode must be one of [${Object.values(Mode).join(', ')}], but "${args[2]}" given.`;
    }
    return args[2];
}

function getDateArg(args: string[]): Date
{
    if (!args[2]) {
        throw `Date is not specified.`;
    }
    const date = new Date(args[2]);
    if (isNaN(date.getTime())) {
        throw `Date must conform the format yyyy-mm-dd, but "${args[1]}" given.`;
    }
    return date;
}
