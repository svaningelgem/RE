#pragma once


#include <string>
using namespace std;


#define BASE(X, name, VIRTUAL) \
class LIBRARY_API name##Base##X { \
public: \
  name##Base##X() \
  : X##publicVar1(1), \
    X##publicVar2(2), \
    X##protectedVar1(3), \
    X##protectedVar2(4), \
    X##privateVar1(5), \
    X##privateVar2(6) \
{ } \
 \
  VIRTUAL ~name##Base##X() { \
    X##publicVar1 = 999; \
  } \
 \
  int X##publicVar1; \
  int X##publicVar2; \
  int X##publicFunc1( int a ) const { return 0; } \
  VIRTUAL const char* X##publicFunc2( const char* a, long b ) { return 0; } \
  static int doSomething() { return 1; } \
 \
protected: \
  int X##protectedVar1; \
  int X##protectedVar2; \
  void * X##protectedFunc1( string a ) { return 0; } \
  VIRTUAL void X##protectedFunc2( double a ) { return; } \
 \
private: \
  int X##privateVar1; \
  int X##privateVar2; \
  VIRTUAL string X##privateFunc1( short a ) { return ""; } \
  const string& X##privateFunc2( const string& b ) { static string a; return a; } \
\
  static double staticVar##X; \
}

#define SUBCLASS(X, Y) \
class LIBRARY_API Class##X { \
public: \
  class LIBRARY_API SubClass##Y { \
  public: \
    int a; \
    int func() { return 0; } \
  }; \
  struct LIBRARY_API SubStruct##Y { \
    int a; \
    int func() { return 0; } \
  }; \
 \
  int pub; \
  int pubFunc() { return 0; } \
  static const Class##X Default() { return Class##X(); }\
  static const Class##X DefaultObj; \
 \
protected: \
  int prot; \
  int protFunc() { return 0; } \
 \
private: \
  int priv; \
  int privFunc() { return 0; } \
}

#define SUBCLASSDEF(X, Y) \
const Class##X Class##X::DefaultObj

#define BASECLASS(X, name) \
{\
public: \
  name() \
    : X##pubVar( 1 ) { \
  } \
  ~name() { \
    X##pubVar = 999; \
  } \
 \
  int X##pubVar; \
  int X##pubFunc() { return 0; } \
}


#define SINGLEDERIVED(X, name, Y, Y1) \
class LIBRARY_API Single##name##Derived##Y##X : Y Y1 \
BASECLASS(X, Single##name##Derived##Y##X)

#define MULTIDERIVED(X, name, Y, Y1, Z, Z1) \
class LIBRARY_API Multi##name##Derived##Y##Z##X : Y Y1, Z Z1 \
BASECLASS(X, Multi##name##Derived##Y##Z##X)




BASE( A, Normal, );
BASE( B, Normal, );
SUBCLASS(MAIN, SUB);
SINGLEDERIVED( C, , public, NormalBaseA );
SINGLEDERIVED( D, , protected, NormalBaseA );
SINGLEDERIVED( E, , private, NormalBaseA );
MULTIDERIVED( F, , public, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( G, , public, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( H, , public, NormalBaseA, private, NormalBaseB );
MULTIDERIVED( I, , protected, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( J, , protected, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( K, , protected, NormalBaseA, private, NormalBaseB );
MULTIDERIVED( L, , private, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( M, , private, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( N, , private, NormalBaseA, private, NormalBaseB );



BASE( A, Virtual, virtual );
BASE( B, Virtual, virtual );
SINGLEDERIVED( C, Virtual, public, VirtualBaseA );
SINGLEDERIVED( D, Virtual, protected, VirtualBaseA );
SINGLEDERIVED( E, Virtual, private, VirtualBaseA );
MULTIDERIVED( F, Virtual, public, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( G, Virtual, public, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( H, Virtual, public, virtual VirtualBaseA, private, VirtualBaseB );
MULTIDERIVED( I, Virtual, protected, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( J, Virtual, protected, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( K, Virtual, protected, virtual VirtualBaseA, private, VirtualBaseB );
MULTIDERIVED( L, Virtual, private, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( M, Virtual, private, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( N, Virtual, private, virtual VirtualBaseA, private, VirtualBaseB );
