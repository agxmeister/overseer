import {format} from "@/utils/date";
import {ApiUrl} from "@/constants/api";
import task from "@/console/commands/task";
import {Mode} from "@/types/Schedule";
import {getActionArg} from "@/console/utils";
import {Context, Setters} from "@/console/run";
import {Link} from "@/types/Link";

enum Action {
    Create = "create",
    Apply = "apply",
    Rollback = "rollback",
    Mode = "mode",
}

export default async function schedule(args: string[], context: Context, setters: Setters): Promise<string[]>
{
    const lines = [];
    try {
        const action = getActionArg(args, Object.values<string>(Action));
        switch (action) {
            case Action.Create:
                const date = getDateArg(args);
                await fetch(ApiUrl.SCHEDULE.replace('{date}', format(date)))
                    .then(res => res.json())
                    .then(data => {
                        setters.setSchedule(data);
                        setters.setMode(Mode.Edit);
                    });
                break;
            case Action.Apply:
                const promises = [];

                const links = context.issues.reduce(
                    (acc: Link[], issue) => acc.concat(issue.links.outward, issue.links.inward),
                    []
                )
                    .filter(link => link.id)
                    .filter(link => link.type === 'Follows')
                    .filter((link, index, self) =>
                        self.findIndex(current => current.id === link.id) === index);

                for (const link of links) {
                    const promise = task([
                        'task',
                        'unlink',
                        `id=${link.id}`
                    ], context, setters);
                    promise.then(output => lines.unshift(...output));
                }

                for (const issue of context.issues) {
                    const promise = task([
                        'task',
                        'resize',
                        issue.key,
                        `begin=${issue.estimatedBeginDate}`,
                        `end=${issue.estimatedEndDate}`
                    ], context, setters);
                    promise.then(output => lines.unshift(...output));

                    for (const link of issue.links.outward) {
                        const promise = task([
                            'task',
                            'link',
                            `from=${link.key}`,
                            `to=${issue.key}`
                        ], context, setters);
                        promise.then(output => lines.unshift(...output));
                    }

                    for (const link of issue.links.inward) {
                        const promise = task([
                            'task',
                            'link',
                            `from=${issue.key}`,
                            `to=${link.key}`
                        ], context, setters);
                        promise.then(output => lines.unshift(...output));
                    }

                    promises.push(promise);
                }
                await Promise.all(promises);
                setters.setSchedule([]);
                setters.setMode(Mode.View);
                break;
            case Action.Rollback:
                setters.setSchedule([]);
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
