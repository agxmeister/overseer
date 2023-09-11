import {getActionArg, getDateArg, getNamedArg} from "./utils";
import {describe} from "node:test";

describe('getActionArg', () => {
    it.each([
        {args: ['test', 'a1', 'param'], actions: ['a1', 'a2'], expected: 'a1'},
        {args: ['test', 'a2', 'param'], actions: ['a1', 'a2'], expected: 'a2'},
    ])('get the action from the list of arguments', ({args, actions, expected}) => {
        expect(getActionArg(args, actions)).toEqual(expected);
    });

    it('missed action in the list of arguments', () => {
        expect(() => getActionArg(['test'], ['a1', 'a2']))
            .toThrow('Action is not specified.');
    });

    it('not existing action in the list of arguments', () => {
        const args = ['test', 'a3'];
        const actions = ['a1', 'a2'];
        expect(() => getActionArg(args, actions))
            .toThrow(`Action must be one of [${actions.join(', ')}], but "${args[1]}" given.`);
    });
});

describe('getNamedArg', () => {
    it.each([
        {args: ['test', 'p1=v1'], name: 'p1', expected: 'v1'},
        {args: ['test', 'p1="v1"'], name: 'p1', expected: '"v1"'},
        {args: ['test', 'a', 'p1=v1'], name: 'p1', expected: 'v1'},
        {args: ['test', 'a', 'p1=v1', 'b'], name: 'p1', expected: 'v1'},
        {args: ['test', 'a', 'p1=', 'b'], name: 'p1', expected: ''},
    ])('get the named parameter from the list of arguments', ({args, name, expected}) => {
        expect(getNamedArg(args, name)).toEqual(expected);
    });

    it.each([
        {args: ['test', 'a', 'p1', 'b'], name: 'p1'},
        {args: ['test', 'a', 'b'], name: 'p1'},
    ])('missed named parameter in the list of arguments', ({args, name}) => {
        expect(() => getNamedArg(args, name))
            .toThrow(`Parameter "${name}" is not specified.`);
    });
})

describe('getDateArg', () => {
    it.each([
        {arg: '2023'},
        {arg: '2023-1'},
        {arg: '2023-01'},
        {arg: '2023-1-2'},
        {arg: '2023-1-02'},
        {arg: '2023-01-02'},
        {arg: '"2023-01-02"'},
    ])('get the date argument', ({arg}) => {
        expect(getDateArg(arg)).toEqual(new Date(arg));
    });

    it.each([
        {arg: ''},
        {arg: 'a'},
        {arg: '2023-13-01'},
        {arg: '2023-01-00'},
        {arg: '2023-01-32'},
    ])('wrong date format', ({arg}) => {
        expect(() => getDateArg(arg))
            .toThrow(`Date must conform the format yyyy-mm-dd, but "${arg}" given.`);
    });
})
