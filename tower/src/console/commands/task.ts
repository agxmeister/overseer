import {getActionArg, getDateArg, getNamedArg} from "@/console/utils";
import {format} from "@/utils/date";

enum Action {
    Resize = "resize",
    Link = "link",
    Unlink = "unlink",
}

export default async function task(args: string[], onTaskResize: Function, onLink: Function, onUnlink: Function): Promise<string[]>
{
    const lines = [];
    try {
        const action = getActionArg(args, Object.values<string>(Action));
        switch (action) {
            case Action.Resize:
                const taskId = getTaskIdArg(args);
                const beginDate = getDateArg(getNamedArg(args, 'begin'));
                const endDate = getDateArg(getNamedArg(args, 'end'));
                await onTaskResize({taskId: taskId, begin: format(beginDate), end: format(endDate)});
                break;
            case Action.Link:
                await onLink(
                    getNamedArg(args, 'from'),
                    getNamedArg(args, 'to')
                );
                break;
            case Action.Unlink:
                await onUnlink(
                    getNamedArg(args, 'from'),
                    getNamedArg(args, 'to')
                );
                break;
        }
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getTaskIdArg(args: string[]): string
{
    if (!args[2]) {
        throw `Task ID is not specified.`;
    }
    return args[2];
}
